###############################################
# Production Dockerfile — PHP-FPM + Nginx
# Single container for Render.com deployment
###############################################

# ── Stage 1: Build assets ────────────────────
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json webpack.config.js ./
COPY assets/ assets/
RUN npm install --legacy-peer-deps && npm run build

# ── Stage 2: Composer dependencies ───────────
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

# ── Stage 3: Production image ────────────────
FROM php:8.2-fpm

# System deps + Nginx
RUN apt-get update && apt-get install -y \
    nginx supervisor \
    git curl zip unzip libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libzip-dev libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite mbstring intl zip gd opcache bcmath \
    && pecl install apcu && docker-php-ext-enable apcu \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# PHP production config
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Nginx config (adapted for single container: fastcgi_pass 127.0.0.1:9000)
RUN rm /etc/nginx/sites-enabled/default
COPY <<'NGINXCONF' /etc/nginx/sites-enabled/default
server {
    listen 80;
    server_name _;
    root /var/www/html/public;
    index index.php;

    client_max_body_size 64M;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }
}
NGINXCONF

# Supervisord to run both PHP-FPM and Nginx
COPY <<'SUPERVISORCONF' /etc/supervisor/conf.d/app.conf
[supervisord]
nodaemon=true
logfile=/dev/stdout
logfile_maxbytes=0

[program:php-fpm]
command=php-fpm -F
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:nginx]
command=nginx -g "daemon off;"
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
SUPERVISORCONF

WORKDIR /var/www/html

# Copy application code
COPY . .

# Copy built assets and vendor from previous stages
COPY --from=assets /app/public/build public/build/
COPY --from=vendor /app/vendor vendor/

# Set permissions
RUN chown -R www-data:www-data var/ public/

# Warm Symfony cache
RUN APP_ENV=prod php bin/console cache:warmup --no-interaction 2>/dev/null || true

EXPOSE 80

# Startup script: init DB + start services
COPY <<'STARTSCRIPT' /usr/local/bin/start.sh
#!/bin/bash
set -e
cd /var/www/html

# Run migrations and load fixtures for demo
php bin/console doctrine:database:create --if-not-exists --no-interaction 2>/dev/null || true
php bin/console doctrine:schema:update --force --no-interaction 2>/dev/null || true
php bin/console doctrine:fixtures:load --no-interaction 2>/dev/null || true

exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
STARTSCRIPT
RUN chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]
