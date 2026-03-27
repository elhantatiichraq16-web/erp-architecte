---
id: ci-cd
title: CI/CD
sidebar_label: CI/CD
description: Pipeline CI/CD GitHub Actions pour l'ERP Architecte — lint, tests, build et déploiement.
---

# Pipeline CI/CD

L'ERP Architecte utilise **GitHub Actions** pour automatiser les vérifications de qualité, les tests et le déploiement. Cette page décrit le pipeline complet.

---

## Vue d'ensemble du pipeline

```
Push / Pull Request
        │
        ▼
┌───────────────┐
│   Lint & QA   │  ← PHP-CS-Fixer, PHPStan, Twig lint
└───────┬───────┘
        │ succès
        ▼
┌───────────────┐
│     Tests     │  ← PHPUnit, coverage
└───────┬───────┘
        │ succès (seulement sur main/develop)
        ▼
┌───────────────┐
│     Build     │  ← Docker image, assets
└───────┬───────┘
        │ succès (seulement sur main)
        ▼
┌───────────────┐
│    Deploy     │  ← Déploiement sur le serveur
└───────────────┘
```

---

## Workflow principal

**Fichier :** `.github/workflows/ci.yml`

```yaml
name: CI/CD Pipeline

on:
  push:
    branches:
      - main
      - develop
      - 'feature/**'
      - 'hotfix/**'
  pull_request:
    branches:
      - main
      - develop

env:
  PHP_VERSION: '8.2'
  NODE_VERSION: '20'
  COMPOSER_FLAGS: '--prefer-dist --no-interaction --no-scripts'

jobs:

  # ─────────────────────────────────────────
  # JOB 1 : Lint et qualité du code
  # ─────────────────────────────────────────
  lint:
    name: "Lint & Code Quality"
    runs-on: ubuntu-latest

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ env.PHP_VERSION }}
          extensions: mbstring, intl, pdo_mysql, zip, opcache
          coverage: none
          tools: cs2pr

      - name: Get Composer cache directory
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT

      - name: Cache Composer packages
        uses: actions/cache@v4
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: ${{ runner.os }}-composer-

      - name: Install Composer dependencies
        run: composer install ${{ env.COMPOSER_FLAGS }}

      - name: Check PHP syntax
        run: find src/ -name "*.php" -exec php -l {} \; | grep -v "No syntax errors"

      - name: PHP-CS-Fixer (code style)
        run: vendor/bin/php-cs-fixer fix --dry-run --format=checkstyle | cs2pr
        continue-on-error: false

      - name: PHPStan (static analysis)
        run: vendor/bin/phpstan analyse src/ --level=6 --no-progress

      - name: Symfony lint YAML
        run: php bin/console lint:yaml config/ --parse-tags

      - name: Symfony lint Twig
        run: php bin/console lint:twig templates/

      - name: Symfony lint containers
        run: php bin/console lint:container

      - name: Symfony security:check
        run: composer audit

  # ─────────────────────────────────────────
  # JOB 2 : Tests unitaires et intégration
  # ─────────────────────────────────────────
  test:
    name: "Tests PHPUnit"
    runs-on: ubuntu-latest
    needs: lint

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: erp_architecte_test
          MYSQL_USER: erp_user
          MYSQL_PASSWORD: erp_password
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=5

    env:
      APP_ENV: test
      DATABASE_URL: "mysql://erp_user:erp_password@127.0.0.1:3306/erp_architecte_test?serverVersion=8.0"

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP with coverage
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ env.PHP_VERSION }}
          extensions: mbstring, intl, pdo_mysql, zip, opcache
          coverage: xdebug

      - name: Cache Composer packages
        uses: actions/cache@v4
        with:
          path: ~/.composer/cache
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}

      - name: Install dependencies
        run: composer install ${{ env.COMPOSER_FLAGS }}

      - name: Create test database
        run: |
          php bin/console doctrine:database:create --env=test --if-not-exists
          php bin/console doctrine:migrations:migrate --env=test --no-interaction

      - name: Load fixtures
        run: php bin/console doctrine:fixtures:load --env=test --no-interaction

      - name: Run PHPUnit tests
        run: |
          vendor/bin/phpunit \
            --coverage-clover coverage.xml \
            --log-junit junit-report.xml \
            --colors=always

      - name: Upload coverage to Codecov
        uses: codecov/codecov-action@v4
        with:
          file: ./coverage.xml
          fail_ci_if_error: false

      - name: Publish test results
        uses: EnricoMi/publish-unit-test-result-action@v2
        if: always()
        with:
          files: junit-report.xml

  # ─────────────────────────────────────────
  # JOB 3 : Build des assets
  # ─────────────────────────────────────────
  build-assets:
    name: "Build Assets (Webpack)"
    runs-on: ubuntu-latest
    needs: lint

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: ${{ env.NODE_VERSION }}
          cache: 'npm'

      - name: Install npm dependencies
        run: npm ci

      - name: Build production assets
        run: npm run build
        env:
          NODE_ENV: production

      - name: Check build output
        run: |
          test -d public/build && echo "Build directory exists"
          test -f public/build/manifest.json && echo "Manifest generated"

      - name: Upload build artifacts
        uses: actions/upload-artifact@v4
        with:
          name: build-assets
          path: public/build/
          retention-days: 7

  # ─────────────────────────────────────────
  # JOB 4 : Build image Docker
  # ─────────────────────────────────────────
  build-docker:
    name: "Build Docker Image"
    runs-on: ubuntu-latest
    needs: [test, build-assets]
    if: github.ref == 'refs/heads/main' || github.ref == 'refs/heads/develop'

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Download build assets
        uses: actions/download-artifact@v4
        with:
          name: build-assets
          path: public/build/

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3

      - name: Log in to GitHub Container Registry
        uses: docker/login-action@v3
        with:
          registry: ghcr.io
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Extract Docker metadata
        id: meta
        uses: docker/metadata-action@v5
        with:
          images: ghcr.io/${{ github.repository }}
          tags: |
            type=ref,event=branch
            type=sha,prefix={{branch}}-
            type=raw,value=latest,enable={{is_default_branch}}

      - name: Build and push Docker image
        uses: docker/build-push-action@v5
        with:
          context: .
          file: ./docker/php/Dockerfile
          push: true
          tags: ${{ steps.meta.outputs.tags }}
          labels: ${{ steps.meta.outputs.labels }}
          cache-from: type=gha
          cache-to: type=gha,mode=max
          build-args: |
            APP_ENV=prod
            BUILD_DATE=${{ github.event.head_commit.timestamp }}
            VCS_REF=${{ github.sha }}

  # ─────────────────────────────────────────
  # JOB 5 : Déploiement en production
  # ─────────────────────────────────────────
  deploy:
    name: "Deploy to Production"
    runs-on: ubuntu-latest
    needs: build-docker
    if: github.ref == 'refs/heads/main'
    environment:
      name: production
      url: https://erp.votre-cabinet.fr

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Deploy via SSH
        uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.PROD_SSH_HOST }}
          username: ${{ secrets.PROD_SSH_USER }}
          key: ${{ secrets.PROD_SSH_KEY }}
          port: ${{ secrets.PROD_SSH_PORT }}
          script: |
            set -e
            cd /opt/erp-archi

            # Pull latest image
            echo "${{ secrets.GITHUB_TOKEN }}" | docker login ghcr.io -u ${{ github.actor }} --password-stdin
            docker compose pull app

            # Run database migrations
            docker compose run --rm app php bin/console doctrine:migrations:migrate --no-interaction

            # Clear Symfony cache
            docker compose run --rm app php bin/console cache:warmup --env=prod

            # Restart with zero downtime
            docker compose up -d --remove-orphans

            # Clean old images
            docker image prune -f

            echo "Deployment completed successfully!"

      - name: Notify deployment (Slack)
        uses: 8398a7/action-slack@v3
        if: always()
        with:
          status: ${{ job.status }}
          fields: repo,message,commit,author,action,eventName,workflow
        env:
          SLACK_WEBHOOK_URL: ${{ secrets.SLACK_WEBHOOK_URL }}
```

---

## Secrets GitHub requis

Configurez ces secrets dans **Settings > Secrets and variables > Actions** de votre dépôt GitHub :

| Secret | Description |
|--------|-------------|
| `PROD_SSH_HOST` | Adresse IP ou hostname du serveur de production |
| `PROD_SSH_USER` | Utilisateur SSH |
| `PROD_SSH_KEY` | Clé privée SSH (RSA ou ED25519) |
| `PROD_SSH_PORT` | Port SSH (défaut: 22) |
| `SLACK_WEBHOOK_URL` | URL du webhook Slack (optionnel) |

---

## Workflow de hotfix

**Fichier :** `.github/workflows/hotfix.yml`

```yaml
name: Hotfix Deploy

on:
  push:
    tags:
      - 'v*.*.*-hotfix*'

jobs:
  deploy-hotfix:
    name: "Deploy Hotfix"
    runs-on: ubuntu-latest
    environment: production

    steps:
      - uses: actions/checkout@v4

      - name: Fast deploy hotfix
        uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.PROD_SSH_HOST }}
          username: ${{ secrets.PROD_SSH_USER }}
          key: ${{ secrets.PROD_SSH_KEY }}
          script: |
            cd /opt/erp-archi
            git pull origin main
            docker compose exec app php bin/console cache:clear --env=prod
            docker compose restart app nginx
```

---

## Badges de statut

Ajoutez ces badges dans votre `README.md` :

```markdown
![CI/CD Pipeline](https://github.com/erp-architecte/erp-archi/actions/workflows/ci.yml/badge.svg)
![Coverage](https://codecov.io/gh/erp-architecte/erp-archi/branch/main/graph/badge.svg)
```

---

:::tip Environnements GitHub
Configurez des **environnements GitHub** (Settings > Environments) pour :
- Ajouter des règles de protection (approbation manuelle avant déploiement en production)
- Définir des secrets spécifiques à chaque environnement (staging, production)
- Limiter les branches autorisées à déployer
:::
