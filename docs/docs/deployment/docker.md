---
id: docker
title: Docker
sidebar_label: Docker
description: Architecture Docker Compose de l'ERP Architecte — services, volumes, réseaux et personnalisation.
---

# Architecture Docker

L'ERP Architecte est entièrement conteneurisé avec **Docker Compose**. Cette page détaille l'architecture des conteneurs, les volumes, les réseaux et les options de personnalisation.

---

## Vue d'ensemble

```
┌─────────────────────────────────────────────────────┐
│               erp-network (bridge)                   │
│                                                      │
│  ┌──────────┐    ┌──────────┐    ┌──────────────┐   │
│  │  nginx   │───►│   app    │───►│     db       │   │
│  │ :8080    │    │ php-fpm  │    │  MySQL :3306  │   │
│  └──────────┘    └──────────┘    └──────────────┘   │
│                                        │             │
│  ┌──────────────┐    ┌──────────────┐  │             │
│  │  phpmyadmin  │───►│   mailer     │  │             │
│  │  :8081       │    │  :8025/:1025 │  │             │
│  └──────────────┘    └──────────────┘  │             │
│                                        │             │
│  mysql_data volume ────────────────────┘             │
└─────────────────────────────────────────────────────┘
```

---

## Services

### app — PHP 8.2 FPM

**Image :** Custom build depuis `docker/php/Dockerfile`

```dockerfile
# docker/php/Dockerfile
FROM php:8.2-fpm-alpine

LABEL maintainer="ERP Architecte <dev@erp-architecte.fr>"

# Dépendances système
RUN apk add --no-cache \
    git \
    curl \
    zip \
    unzip \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    nodejs \
    npm

# Extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        intl \
        zip \
        opcache \
        gd \
        exif \
        bcmath \
        mbstring

# Configuration PHP custom
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Droits
RUN chown -R www-data:www-data /var/www/html

USER www-data

EXPOSE 9000
```

**Configuration :**

```yaml
app:
  build:
    context: .
    dockerfile: docker/php/Dockerfile
    args:
      - APP_ENV=${APP_ENV:-dev}
  volumes:
    - .:/var/www/html:cached
    - ./docker/php/php.ini:/usr/local/etc/php/conf.d/custom.ini:ro
  environment:
    - APP_ENV=${APP_ENV:-dev}
    - APP_SECRET=${APP_SECRET}
    - DATABASE_URL=${DATABASE_URL}
    - MAILER_DSN=${MAILER_DSN}
  depends_on:
    db:
      condition: service_healthy
  networks:
    - erp-network
  restart: unless-stopped
```

---

### nginx — Serveur web

**Image :** `nginx:1.25-alpine`

```yaml
nginx:
  image: nginx:1.25-alpine
  ports:
    - "${NGINX_PORT:-8080}:80"
  volumes:
    - .:/var/www/html:cached
    - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
    - nginx_logs:/var/log/nginx
  depends_on:
    - app
  networks:
    - erp-network
  restart: unless-stopped
  healthcheck:
    test: ["CMD", "wget", "-q", "--spider", "http://localhost/"]
    interval: 30s
    timeout: 10s
    retries: 3
```

**Configuration Nginx complète :**

```nginx
# docker/nginx/default.conf
upstream php_fpm {
    server app:9000;
    keepalive 16;
}

server {
    listen 80;
    server_name _;
    root /var/www/html/public;
    index index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Logs
    access_log /var/log/nginx/access.log combined;
    error_log /var/log/nginx/error.log warn;

    # Max upload size
    client_max_body_size 50M;

    # Gzip
    gzip on;
    gzip_vary on;
    gzip_types text/plain text/css application/json application/javascript
               application/xml+rss text/xml image/svg+xml;
    gzip_min_length 1024;

    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # Main router
    location / {
        try_files $uri /index.php$is_args$args;
    }

    # PHP-FPM
    location ~ ^/index\.php(/|$) {
        fastcgi_pass php_fpm;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_param HTTP_PROXY "";
        fastcgi_read_timeout 120s;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        internal;
    }

    # Block direct PHP access
    location ~ \.php$ {
        return 404;
    }
}
```

---

### db — MySQL 8.0

**Image :** `mysql:8.0`

```yaml
db:
  image: mysql:8.0
  environment:
    MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD:-root_secret}
    MYSQL_DATABASE: ${MYSQL_DATABASE:-erp_architecte}
    MYSQL_USER: ${MYSQL_USER:-erp_user}
    MYSQL_PASSWORD: ${MYSQL_PASSWORD:-erp_password}
    MYSQL_CHARSET: utf8mb4
    MYSQL_COLLATION: utf8mb4_unicode_ci
  ports:
    - "${MYSQL_PORT:-3306}:3306"
  volumes:
    - mysql_data:/var/lib/mysql
    - ./docker/mysql/init.sql:/docker-entrypoint-initdb.d/init.sql:ro
    - ./docker/mysql/my.cnf:/etc/mysql/conf.d/custom.cnf:ro
  networks:
    - erp-network
  healthcheck:
    test: ["CMD", "mysqladmin", "ping", "-h", "127.0.0.1", "-u", "root", "-p${MYSQL_ROOT_PASSWORD:-root_secret}"]
    interval: 5s
    timeout: 10s
    retries: 10
    start_period: 30s
  restart: unless-stopped
```

**Configuration MySQL :**

```ini
# docker/mysql/my.cnf
[mysqld]
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
default-authentication-plugin = mysql_native_password

# Performance
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
max_connections = 100
query_cache_type = 0

# Timezone
default-time-zone = '+01:00'
```

---

### mailer — Mailpit

**Image :** `axllent/mailpit:latest`

```yaml
mailer:
  image: axllent/mailpit:latest
  ports:
    - "${MAILPIT_HTTP_PORT:-8025}:8025"   # Interface web
    - "${MAILPIT_SMTP_PORT:-1025}:1025"   # SMTP
  environment:
    MP_MAX_MESSAGES: 500
    MP_SMTP_AUTH_ACCEPT_ANY: 1
    MP_SMTP_AUTH_ALLOW_INSECURE: 1
  networks:
    - erp-network
  restart: unless-stopped
```

---

### phpmyadmin

**Image :** `phpmyadmin:5.2`

```yaml
phpmyadmin:
  image: phpmyadmin:5.2
  ports:
    - "${PMA_PORT:-8081}:80"
  environment:
    PMA_HOST: db
    PMA_PORT: 3306
    PMA_USER: root
    PMA_PASSWORD: ${MYSQL_ROOT_PASSWORD:-root_secret}
    UPLOAD_LIMIT: 100M
  depends_on:
    db:
      condition: service_healthy
  networks:
    - erp-network
  restart: unless-stopped
```

---

## Volumes

```yaml
volumes:
  mysql_data:
    driver: local
    # Pour persister sur un chemin spécifique :
    # driver_opts:
    #   type: none
    #   o: bind
    #   device: /data/erp-mysql

  nginx_logs:
    driver: local
```

### Sauvegarde des volumes

```bash
# Sauvegarder la base de données
docker compose exec db mysqldump \
    -u root -p${MYSQL_ROOT_PASSWORD} \
    --single-transaction \
    --routines \
    --triggers \
    erp_architecte > backup_$(date +%Y%m%d_%H%M%S).sql

# Sauvegarder les uploads (documents)
docker compose cp app:/var/www/html/public/uploads ./backup_uploads_$(date +%Y%m%d)

# Restaurer la base de données
docker compose exec -T db mysql \
    -u root -p${MYSQL_ROOT_PASSWORD} \
    erp_architecte < backup_20240101_120000.sql
```

---

## Reseaux

```yaml
networks:
  erp-network:
    driver: bridge
    ipam:
      config:
        - subnet: 172.20.0.0/16
```

Tous les services communiquent via le réseau interne `erp-network`. Seuls les ports explicitement exposés dans `ports:` sont accessibles depuis l'hôte.

---

## Personnalisation

### Changer les ports exposés

Créez un fichier `docker-compose.override.yml` (non commité) :

```yaml
# docker-compose.override.yml
services:
  nginx:
    ports:
      - "9080:80"      # Port 9080 au lieu de 8080
  phpmyadmin:
    ports:
      - "9081:80"
  mailer:
    ports:
      - "9025:8025"
      - "9025:1025"
  db:
    ports:
      - "33060:3306"   # Accès direct MySQL depuis l'hôte
```

### Ajouter un service Redis (cache)

```yaml
# Dans docker-compose.override.yml
services:
  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    networks:
      - erp-network

  app:
    environment:
      REDIS_URL: redis://redis:6379
```

### Construire avec des arguments

```bash
# Build avec APP_ENV=prod
docker compose build --build-arg APP_ENV=prod app

# Forcer la reconstruction sans cache
docker compose build --no-cache app
```

---

## Commandes utiles

```bash
# Démarrer tous les services
docker compose up -d

# Voir les logs en temps réel
docker compose logs -f

# Logs d'un service spécifique
docker compose logs -f nginx
docker compose logs -f app

# Ouvrir un shell dans le conteneur app
docker compose exec app sh

# Exécuter une commande Symfony
docker compose exec app php bin/console cache:clear
docker compose exec app php bin/console doctrine:migrations:migrate

# Redémarrer un service spécifique
docker compose restart nginx

# Arrêter tous les services
docker compose down

# Supprimer tout (conteneurs + volumes)
docker compose down -v

# Voir l'utilisation des ressources
docker compose top
docker stats
```
