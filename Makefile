# ─── ERP Architecte — Commandes ─────────────────────
.DEFAULT_GOAL := help
DOCKER_COMP   = docker compose
PHP_CONT      = $(DOCKER_COMP) exec php
SYMFONY       = $(PHP_CONT) php bin/console
NPM           = $(PHP_CONT) npm
COMPOSER      = $(PHP_CONT) composer

# Detect podman-compose fallback
ifeq ($(shell command -v docker 2>/dev/null),)
  DOCKER_COMP = podman-compose
  PHP_CONT    = $(DOCKER_COMP) exec php
endif

.PHONY: help
help: ## Liste des commandes
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# ─── Docker ──────────────────────────────────────────
.PHONY: build up down restart logs ps
build: ## Build les containers
	$(DOCKER_COMP) build --no-cache

up: ## Démarrer les containers
	$(DOCKER_COMP) up -d

down: ## Arrêter les containers
	$(DOCKER_COMP) down

restart: down up ## Redémarrer

logs: ## Voir les logs (tous)
	$(DOCKER_COMP) logs -f

logs-php: ## Logs PHP
	$(DOCKER_COMP) logs -f php

logs-nginx: ## Logs Nginx
	$(DOCKER_COMP) logs -f nginx

ps: ## État des containers
	$(DOCKER_COMP) ps

# ─── Symfony ─────────────────────────────────────────
.PHONY: sf-install sf-migrate sf-fixtures sf-cache sf-routes
sf-install: ## Install Symfony (première fois)
	$(COMPOSER) install
	$(PHP_CONT) php bin/console doctrine:database:create --if-not-exists
	$(PHP_CONT) php bin/console doctrine:migrations:migrate --no-interaction
	$(PHP_CONT) php bin/console doctrine:fixtures:load --no-interaction

sf-migrate: ## Lancer les migrations
	$(PHP_CONT) php bin/console make:migration
	$(PHP_CONT) php bin/console doctrine:migrations:migrate --no-interaction

sf-fixtures: ## Charger les fixtures
	$(PHP_CONT) php bin/console doctrine:fixtures:load --no-interaction

sf-cache: ## Vider le cache
	$(PHP_CONT) php bin/console cache:clear

sf-routes: ## Lister les routes
	$(PHP_CONT) php bin/console debug:router

# ─── Assets ──────────────────────────────────────────
.PHONY: npm-install npm-build npm-watch
npm-install: ## Install packages npm
	$(NPM) install

npm-build: ## Build assets (prod)
	$(NPM) run build

npm-watch: ## Watch assets (dev)
	$(NPM) run watch

# ─── Full Setup ──────────────────────────────────────
.PHONY: init
init: build up ## Setup complet (première fois)
	@echo "⏳ Attente MySQL..."
	@sleep 15
	$(COMPOSER) install --no-interaction
	$(NPM) install
	$(NPM) run build
	$(PHP_CONT) php bin/console doctrine:database:create --if-not-exists
	$(PHP_CONT) php bin/console doctrine:migrations:migrate --no-interaction
	$(PHP_CONT) php bin/console doctrine:fixtures:load --no-interaction
	$(PHP_CONT) php bin/console cache:clear
	@echo ""
	@echo "✅ ERP Architecte prêt !"
	@echo "🌐 App:        http://localhost:8080"
	@echo "🗄️  phpMyAdmin: http://localhost:8081"
	@echo "📧 Mailpit:    http://localhost:8025"
	@echo "📚 Docs:       http://localhost:3000"

# ─── Debug ───────────────────────────────────────────
.PHONY: shell mysql-shell
shell: ## Shell dans le container PHP
	$(PHP_CONT) bash

mysql-shell: ## Shell MySQL
	$(DOCKER_COMP) exec mysql mysql -u erp_user -perp_secret erp_architecte
