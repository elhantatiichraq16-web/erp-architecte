---
id: configuration
title: Configuration
sidebar_label: Configuration
description: Variables d'environnement, services Docker et options de configuration de l'ERP Architecte.
---

# Configuration

Cette page décrit toutes les options de configuration disponibles pour personnaliser votre installation de l'ERP Architecte.

---

## Variables d'environnement (.env)

Le fichier `.env` à la racine du projet contient toutes les variables d'environnement. Ne commitez jamais ce fichier avec des valeurs sensibles de production — utilisez `.env.local` ou les secrets de votre CI/CD.

### Application Symfony

```dotenv
###> symfony/framework-bundle ###
APP_ENV=dev
APP_SECRET=change_me_to_a_random_32_char_string
###< symfony/framework-bundle ###
```

| Variable | Valeur par défaut | Description |
|----------|-------------------|-------------|
| `APP_ENV` | `dev` | Environnement Symfony : `dev`, `test`, `prod` |
| `APP_SECRET` | *(à définir)* | Clé secrète pour les tokens CSRF et sessions. **Doit être changée en production.** |

:::danger Sécurité
Ne laissez jamais `APP_SECRET` avec sa valeur par défaut en production. Générez une valeur aléatoire :
```bash
php -r "echo bin2hex(random_bytes(16));"
```
:::

---

### Base de données

```dotenv
###> doctrine/doctrine-bundle ###
DATABASE_URL="mysql://erp_user:erp_password@db:3306/erp_architecte?serverVersion=8.0&charset=utf8mb4"
###< doctrine/doctrine-bundle ###
```

| Paramètre | Description |
|-----------|-------------|
| `erp_user` | Nom d'utilisateur MySQL |
| `erp_password` | Mot de passe MySQL |
| `db` | Hôte (nom du service Docker) |
| `3306` | Port MySQL |
| `erp_architecte` | Nom de la base de données |
| `serverVersion=8.0` | Version MySQL (important pour les migrations) |
| `charset=utf8mb4` | Support complet Unicode (emojis, caractères spéciaux) |

**Variables Docker correspondantes :**

```dotenv
MYSQL_ROOT_PASSWORD=root_secret
MYSQL_DATABASE=erp_architecte
MYSQL_USER=erp_user
MYSQL_PASSWORD=erp_password
```

---

### Messagerie (SMTP / Mailer)

```dotenv
###> symfony/mailer ###
MAILER_DSN=smtp://mailer:1025
###< symfony/mailer ###

MAIL_FROM=noreply@erp-architecte.fr
MAIL_FROM_NAME="ERP Architecte"
```

| Variable | Développement | Production |
|----------|--------------|------------|
| `MAILER_DSN` | `smtp://mailer:1025` (Mailpit) | `smtp://user:pass@smtp.host:587` |
| `MAIL_FROM` | `noreply@erp-architecte.fr` | Adresse de votre domaine |
| `MAIL_FROM_NAME` | `ERP Architecte` | Nom affiché dans les emails |

**Fournisseurs SMTP supportés :**

```dotenv
# SendGrid
MAILER_DSN=sendgrid+smtp://SG.xxx@default

# Mailgun
MAILER_DSN=mailgun+smtp://key:domain@default

# Amazon SES
MAILER_DSN=ses+smtp://ACCESS_KEY:SECRET@default

# OVH / Gmail / etc.
MAILER_DSN=smtp://user:password@smtp.gmail.com:587?encryption=tls
```

---

### Upload de fichiers

```dotenv
UPLOAD_DIR=uploads
MAX_UPLOAD_SIZE=10M
ALLOWED_EXTENSIONS=pdf,doc,docx,xls,xlsx,jpg,jpeg,png,dwg,rvt,skp
```

| Variable | Description |
|----------|-------------|
| `UPLOAD_DIR` | Répertoire de stockage des fichiers (relatif à `public/`) |
| `MAX_UPLOAD_SIZE` | Taille maximale par fichier |
| `ALLOWED_EXTENSIONS` | Extensions autorisées (CSV) |

---

### Pagination et affichage

```dotenv
ITEMS_PER_PAGE=15
DEFAULT_CURRENCY=EUR
DEFAULT_VAT_RATE=20
DEFAULT_TIMEZONE=Europe/Paris
DEFAULT_LOCALE=fr
```

| Variable | Valeur par défaut | Description |
|----------|-------------------|-------------|
| `ITEMS_PER_PAGE` | `15` | Nombre d'éléments par page dans les listes |
| `DEFAULT_CURRENCY` | `EUR` | Devise par défaut |
| `DEFAULT_VAT_RATE` | `20` | Taux de TVA par défaut (%) |
| `DEFAULT_TIMEZONE` | `Europe/Paris` | Fuseau horaire |
| `DEFAULT_LOCALE` | `fr` | Langue de l'interface |

---

## Services Docker Compose

Le fichier `docker-compose.yml` définit l'infrastructure complète :

```yaml
version: '3.8'

services:

  # ─── Application PHP ────────────────────────────────
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    volumes:
      - .:/var/www/html
      - ./docker/php/php.ini:/usr/local/etc/php/conf.d/custom.ini
    environment:
      - APP_ENV=dev
    depends_on:
      db:
        condition: service_healthy
    networks:
      - erp-network

  # ─── Serveur web Nginx ──────────────────────────────
  nginx:
    image: nginx:1.25-alpine
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app
    networks:
      - erp-network

  # ─── Base de données MySQL ──────────────────────────
  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD:-root_secret}
      MYSQL_DATABASE: ${MYSQL_DATABASE:-erp_architecte}
      MYSQL_USER: ${MYSQL_USER:-erp_user}
      MYSQL_PASSWORD: ${MYSQL_PASSWORD:-erp_password}
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 5s
      timeout: 10s
      retries: 10
    networks:
      - erp-network

  # ─── phpMyAdmin ─────────────────────────────────────
  phpmyadmin:
    image: phpmyadmin:5.2
    ports:
      - "8081:80"
    environment:
      PMA_HOST: db
      PMA_PORT: 3306
      PMA_USER: root
      PMA_PASSWORD: ${MYSQL_ROOT_PASSWORD:-root_secret}
    depends_on:
      - db
    networks:
      - erp-network

  # ─── Mailpit (SMTP catch-all) ───────────────────────
  mailer:
    image: axllent/mailpit:latest
    ports:
      - "8025:8025"   # Interface web
      - "1025:1025"   # SMTP
    networks:
      - erp-network

volumes:
  mysql_data:

networks:
  erp-network:
    driver: bridge
```

---

## Configuration Nginx

Le fichier `docker/nginx/default.conf` configure le serveur web :

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass app:9000;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript;
    gzip_min_length 1024;

    # Cache des assets statiques
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    client_max_body_size 20M;
    error_log /var/log/nginx/error.log;
    access_log /var/log/nginx/access.log;
}
```

---

## Configuration PHP

Le fichier `docker/php/php.ini` personnalise PHP :

```ini
[PHP]
memory_limit = 256M
max_execution_time = 60
upload_max_filesize = 20M
post_max_size = 20M
max_input_vars = 3000
date.timezone = Europe/Paris

[opcache]
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 0    ; 0 en dev, 60 en prod
```

---

## Fichier .env.local (surcharges locales)

Pour surcharger des variables sans modifier `.env`, créez un fichier `.env.local` (ignoré par git) :

```dotenv
# .env.local — surcharges personnelles (non commité)
APP_ENV=dev
DATABASE_URL="mysql://erp_user:mon_mdp_perso@127.0.0.1:3306/erp_architecte?serverVersion=8.0"
MAILER_DSN=smtp://localhost:1025
```

**Priorité des fichiers .env (du moins prioritaire au plus prioritaire) :**

```
.env
.env.local
.env.{APP_ENV}           # ex: .env.dev, .env.prod
.env.{APP_ENV}.local     # ex: .env.dev.local
```

---

:::tip Override Docker Compose
Pour personnaliser la configuration Docker sans modifier `docker-compose.yml`, créez un fichier `docker-compose.override.yml` (ignoré par git) :

```yaml
# docker-compose.override.yml
services:
  nginx:
    ports:
      - "9090:80"   # Port différent pour éviter les conflits
  db:
    ports:
      - "33060:3306"  # Accès direct à MySQL depuis l'hôte
```
:::
