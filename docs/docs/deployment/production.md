---
id: production
title: Déploiement en Production
sidebar_label: Production
description: Checklist complète pour déployer l'ERP Architecte en production — SSL, optimisation, monitoring.
---

# Déploiement en Production

Ce guide couvre toutes les étapes et vérifications nécessaires pour déployer l'ERP Architecte dans un environnement de production sécurisé et performant.

---

## Checklist de déploiement

### Infrastructure minimale recommandée

| Composant | Minimum | Recommandé |
|-----------|---------|------------|
| **CPU** | 2 vCPU | 4 vCPU |
| **RAM** | 4 Go | 8 Go |
| **Disque** | 50 Go SSD | 100 Go SSD |
| **OS** | Ubuntu 22.04 LTS | Ubuntu 24.04 LTS |
| **Docker** | 24.x | 27.x |

---

## 1. Variables d'environnement de production

Créez un fichier `.env.prod` (ou utilisez les variables d'environnement système) :

```dotenv
# ─── Symfony ──────────────────────────────────────────────
APP_ENV=prod
APP_SECRET=<générer avec: php -r "echo bin2hex(random_bytes(32));">
APP_DEBUG=0

# ─── Base de données ──────────────────────────────────────
DATABASE_URL="mysql://erp_prod_user:STRONG_PASSWORD@db:3306/erp_architecte_prod?serverVersion=8.0&charset=utf8mb4"

# ─── Mailer ───────────────────────────────────────────────
MAILER_DSN=smtp://apikey:SG.xxxxx@smtp.sendgrid.net:587
MAIL_FROM=noreply@votre-cabinet.fr
MAIL_FROM_NAME="Cabinet Architecture"

# ─── Uploads ──────────────────────────────────────────────
UPLOAD_DIR=uploads

# ─── MySQL ────────────────────────────────────────────────
MYSQL_ROOT_PASSWORD=VERY_STRONG_ROOT_PASSWORD
MYSQL_DATABASE=erp_architecte_prod
MYSQL_USER=erp_prod_user
MYSQL_PASSWORD=STRONG_USER_PASSWORD
```

:::danger Sécurité des secrets
- Ne stockez JAMAIS les secrets dans le dépôt Git
- Utilisez un gestionnaire de secrets (HashiCorp Vault, AWS Secrets Manager, GitHub Secrets)
- Rotez les mots de passe régulièrement
- L'`APP_SECRET` doit être unique et aléatoire (minimum 32 caractères)
:::

---

## 2. Configuration SSL/HTTPS

### Option A : Nginx avec Let's Encrypt (Certbot)

```bash
# Installer Certbot
sudo apt install certbot python3-certbot-nginx

# Obtenir un certificat
sudo certbot --nginx -d erp.votre-cabinet.fr

# Renouvellement automatique (déjà configuré par certbot)
sudo systemctl enable certbot.timer
```

**Configuration Nginx avec SSL :**

```nginx
# /etc/nginx/conf.d/erp.conf

# Redirection HTTP → HTTPS
server {
    listen 80;
    server_name erp.votre-cabinet.fr;
    return 301 https://$server_name$request_uri;
}

# HTTPS
server {
    listen 443 ssl http2;
    server_name erp.votre-cabinet.fr;

    ssl_certificate /etc/letsencrypt/live/erp.votre-cabinet.fr/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/erp.votre-cabinet.fr/privkey.pem;

    # Modern SSL configuration (Mozilla SSL Config Generator)
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:...;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 1d;
    ssl_session_tickets off;

    # HSTS
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;

    # OCSP stapling
    ssl_stapling on;
    ssl_stapling_verify on;

    root /var/www/html/public;
    index index.php;

    # ... (reste de la configuration Nginx)
}
```

### Option B : Traefik (reverse proxy avec SSL automatique)

```yaml
# docker-compose.prod.yml
services:
  traefik:
    image: traefik:v3
    command:
      - "--api.insecure=false"
      - "--providers.docker=true"
      - "--providers.docker.exposedbydefault=false"
      - "--entrypoints.web.address=:80"
      - "--entrypoints.websecure.address=:443"
      - "--certificatesresolvers.letsencrypt.acme.httpchallenge=true"
      - "--certificatesresolvers.letsencrypt.acme.httpchallenge.entrypoint=web"
      - "--certificatesresolvers.letsencrypt.acme.email=admin@votre-cabinet.fr"
      - "--certificatesresolvers.letsencrypt.acme.storage=/letsencrypt/acme.json"
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:ro
      - traefik_certs:/letsencrypt

  nginx:
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.erp.rule=Host(`erp.votre-cabinet.fr`)"
      - "traefik.http.routers.erp.entrypoints=websecure"
      - "traefik.http.routers.erp.tls.certresolver=letsencrypt"
      # Redirection HTTP → HTTPS
      - "traefik.http.routers.erp-http.rule=Host(`erp.votre-cabinet.fr`)"
      - "traefik.http.routers.erp-http.entrypoints=web"
      - "traefik.http.routers.erp-http.middlewares=redirect-to-https"
      - "traefik.http.middlewares.redirect-to-https.redirectscheme.scheme=https"
```

---

## 3. Optimisations Symfony

### Compiler le cache en production

```bash
# Vider et reconstruire le cache
docker compose exec app php bin/console cache:warmup --env=prod

# Vider l'OPcache
docker compose exec app php bin/console cache:pool:clear cache.app
```

### Configuration OPcache pour la production

```ini
# docker/php/opcache.ini
[opcache]
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0    ; 0 en PROD (ne pas vérifier les timestamps)
opcache.revalidate_freq=0
opcache.fast_shutdown=1
opcache.enable_cli=0
```

### Composer en mode production

```bash
# Installer sans les dépendances de dev
docker compose exec app composer install --no-dev --optimize-autoloader --classmap-authoritative

# Ou via le build Docker
RUN composer install --no-dev --optimize-autoloader --classmap-authoritative --no-scripts
```

### Assets en production

```bash
# Build des assets en mode production (minification, versioning)
docker compose exec app npm run build
# ou
NODE_ENV=production npm run build
```

---

## 4. Securite

### Pare-feu (UFW)

```bash
# Autoriser seulement les ports nécessaires
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp     # SSH
sudo ufw allow 80/tcp     # HTTP
sudo ufw allow 443/tcp    # HTTPS
sudo ufw enable

# Bloquer l'accès direct aux ports internes Docker
# (phpMyAdmin, Mailpit ne doivent pas être accessibles en production)
sudo ufw deny 8025/tcp
sudo ufw deny 8081/tcp
sudo ufw deny 3306/tcp
```

### Fail2Ban (protection SSH et HTTP)

```bash
sudo apt install fail2ban

# Configuration
cat > /etc/fail2ban/jail.local << EOF
[DEFAULT]
bantime = 1h
findtime = 10m
maxretry = 5

[sshd]
enabled = true

[nginx-http-auth]
enabled = true

[nginx-botsearch]
enabled = true
EOF

sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

### Désactiver les services de développement

En production, assurez-vous que phpMyAdmin et Mailpit ne sont PAS exposés :

```yaml
# docker-compose.prod.yml — Ne pas inclure phpmyadmin et mailer
# ou surcharger leurs ports pour les bloquer
services:
  phpmyadmin:
    profiles: ["dev"]   # N'est démarré qu'avec --profile dev
  mailer:
    profiles: ["dev"]
```

---

## 5. Sauvegardes automatiques

```bash
#!/bin/bash
# /opt/scripts/backup-erp.sh

BACKUP_DIR="/opt/backups/erp"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

mkdir -p $BACKUP_DIR

# Sauvegarde BDD
echo "Backing up database..."
docker compose -f /opt/erp-archi/docker-compose.yml exec -T db \
    mysqldump -u root -p${MYSQL_ROOT_PASSWORD} \
    --single-transaction --routines --triggers \
    erp_architecte_prod | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Sauvegarde des uploads
echo "Backing up uploads..."
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz \
    /opt/erp-archi/public/uploads/

# Supprimer les anciennes sauvegardes
find $BACKUP_DIR -name "*.gz" -mtime +$RETENTION_DAYS -delete

echo "Backup completed: $DATE"
```

```bash
# Ajouter au crontab
sudo crontab -e
# Sauvegarde quotidienne à 3h du matin
0 3 * * * /opt/scripts/backup-erp.sh >> /var/log/erp-backup.log 2>&1
```

---

## 6. Monitoring

### Healthcheck endpoint

```php
// src/Controller/HealthController.php
#[Route('/health', name: 'health')]
public function health(Connection $connection): JsonResponse
{
    try {
        $connection->executeQuery('SELECT 1');
        $dbStatus = 'ok';
    } catch (\Exception $e) {
        $dbStatus = 'error: ' . $e->getMessage();
    }

    $status = $dbStatus === 'ok' ? 'healthy' : 'unhealthy';
    $code = $status === 'healthy' ? 200 : 503;

    return $this->json([
        'status' => $status,
        'database' => $dbStatus,
        'timestamp' => (new \DateTimeImmutable())->format('c'),
        'version' => $_ENV['APP_VERSION'] ?? 'unknown',
    ], $code);
}
```

### Uptime Kuma (monitoring simple)

```bash
# Déployer Uptime Kuma
docker run -d \
    --name uptime-kuma \
    -p 3001:3001 \
    -v uptime-kuma:/app/data \
    --restart unless-stopped \
    louislam/uptime-kuma:1
```

Surveiller les URLs :
- `https://erp.votre-cabinet.fr/health` — Santé applicative
- `https://erp.votre-cabinet.fr` — Page principale

### Logs centralisés

```bash
# Voir les logs applicatifs
tail -f /opt/erp-archi/var/log/prod.log

# Logs Docker
docker compose logs -f --tail=100 app
docker compose logs -f --tail=100 nginx
```

---

## 7. Mise a jour de l'application

```bash
#!/bin/bash
# /opt/scripts/update-erp.sh
set -e

cd /opt/erp-archi

echo "=== ERP Architecte — Mise à jour ==="

# 1. Activer le mode maintenance
# (optionnel si vous avez une page de maintenance)

# 2. Récupérer les nouvelles images
docker compose pull

# 3. Sauvegarder la BDD avant la mise à jour
/opt/scripts/backup-erp.sh

# 4. Exécuter les migrations
docker compose run --rm app php bin/console doctrine:migrations:migrate --no-interaction --env=prod

# 5. Vider le cache
docker compose run --rm app php bin/console cache:warmup --env=prod

# 6. Redémarrer les services
docker compose up -d --remove-orphans

# 7. Vérifier le statut
sleep 5
curl -sf https://erp.votre-cabinet.fr/health || echo "WARNING: Health check failed!"

echo "=== Mise à jour terminée ==="
```

---

## Checklist finale avant mise en production

- [ ] `APP_ENV=prod` et `APP_DEBUG=0` dans les variables d'environnement
- [ ] `APP_SECRET` généré aléatoirement (32+ caractères)
- [ ] Certificat SSL valide et HTTPS forcé
- [ ] Ports 8025 (Mailpit) et 8081 (phpMyAdmin) non exposés
- [ ] Pare-feu configuré (UFW ou équivalent)
- [ ] Fail2Ban actif
- [ ] Sauvegardes automatiques configurées et testées
- [ ] OPcache activé avec `validate_timestamps=0`
- [ ] Assets compilés en mode production (`npm run build`)
- [ ] Composer installé sans dépendances de dev (`--no-dev`)
- [ ] Cache Symfony warmé (`cache:warmup --env=prod`)
- [ ] Endpoint `/health` opérationnel
- [ ] Monitoring configuré (Uptime Kuma ou équivalent)
- [ ] Email de test envoyé depuis la production
- [ ] Compte administrateur créé avec un mot de passe fort
- [ ] Sauvegardes testées (restauration vérifiée)
