---
id: installation
title: Installation
sidebar_label: Installation
description: Guide d'installation complet de l'ERP Architecte avec Docker ou Podman.
---

# Installation

Ce guide vous accompagne pas à pas pour installer et démarrer l'ERP Architecte sur votre machine de développement.

---

## Prérequis

Avant de commencer, assurez-vous d'avoir les outils suivants installés sur votre système.

### Docker (recommandé)

| Outil | Version minimale | Installation |
|-------|-----------------|--------------|
| **Docker Engine** | 24.x | [docs.docker.com](https://docs.docker.com/engine/install/) |
| **Docker Compose** | 2.x (plugin) | Inclus avec Docker Desktop |
| **Git** | 2.x | [git-scm.com](https://git-scm.com/) |
| **Make** | 4.x | Via package manager |

:::info Docker Desktop vs Docker Engine
Sur **macOS et Windows**, installez [Docker Desktop](https://www.docker.com/products/docker-desktop/) qui inclut Docker Engine, Docker Compose et une interface graphique.

Sur **Linux**, installez Docker Engine + le plugin Compose séparément.
:::

### Podman (alternative)

Si vous préférez Podman (rootless par défaut) :

```bash
# Fedora / RHEL
sudo dnf install podman podman-compose

# Ubuntu
sudo apt install podman

# macOS
brew install podman
podman machine init && podman machine start
```

:::warning Podman et Docker Compose
Avec Podman, utilisez `podman-compose` à la place de `docker compose`. Les commandes `make` du projet sont configurées pour Docker ; adaptez-les si nécessaire.
:::

---

## Etape 1 — Cloner le dépôt

```bash
git clone https://github.com/erp-architecte/erp-archi.git
cd erp-archi
```

Vérifiez la structure du projet :

```
erp-archi/
├── docker/
│   ├── nginx/
│   │   └── default.conf
│   └── php/
│       └── Dockerfile
├── docker-compose.yml
├── docker-compose.override.yml   # (optionnel, ignoré par git)
├── Makefile
├── .env
├── .env.example
└── src/
```

---

## Etape 2 — Configurer les variables d'environnement

Copiez le fichier d'exemple et adaptez-le :

```bash
cp .env.example .env
```

Les valeurs par défaut fonctionnent pour un environnement de développement local. Consultez [Configuration](/getting-started/configuration) pour les détails.

---

## Etape 3 — Démarrer les conteneurs Docker

```bash
docker compose up -d
```

Cette commande démarre quatre services :

| Service | Description | Port |
|---------|-------------|------|
| `app` | PHP 8.2 + Symfony | — |
| `nginx` | Serveur web Nginx | 8080 |
| `db` | MySQL 8.0 | 3306 (interne) |
| `mailer` | Mailpit (catch-all SMTP) | 8025 |
| `phpmyadmin` | Interface MySQL | 8081 |

Vérifiez que tous les conteneurs sont démarrés :

```bash
docker compose ps
```

Résultat attendu :

```
NAME                   STATUS          PORTS
erp-archi-app-1        Up              9000/tcp
erp-archi-nginx-1      Up              0.0.0.0:8080->80/tcp
erp-archi-db-1         Up              3306/tcp
erp-archi-mailer-1     Up              0.0.0.0:8025->8025/tcp
erp-archi-phpmyadmin-1 Up              0.0.0.0:8081->80/tcp
```

---

## Etape 4 — Initialiser l'application

La commande `make init` exécute toutes les étapes d'initialisation :

```bash
make init
```

Cette commande effectue dans l'ordre :

1. **Installation des dépendances Composer**
   ```bash
   docker compose exec app composer install
   ```

2. **Création de la base de données**
   ```bash
   docker compose exec app php bin/console doctrine:database:create --if-not-exists
   ```

3. **Exécution des migrations**
   ```bash
   docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
   ```

4. **Chargement des données de démonstration** *(fixtures)*
   ```bash
   docker compose exec app php bin/console doctrine:fixtures:load --no-interaction
   ```

5. **Compilation des assets**
   ```bash
   docker compose exec app npm install
   docker compose exec app npm run build
   ```

---

## Etape 5 — Accéder à l'application

Une fois l'initialisation terminée, ouvrez les URLs suivantes dans votre navigateur :

| URL | Service | Description |
|-----|---------|-------------|
| **[http://localhost:8080](http://localhost:8080)** | ERP Application | Interface principale |
| **[http://localhost:8081](http://localhost:8081)** | phpMyAdmin | Administration BDD |
| **[http://localhost:8025](http://localhost:8025)** | Mailpit | Emails de développement |
| **[http://localhost:3000](http://localhost:3000)** | Documentation | Ce site |

### Identifiants de démonstration

Après le chargement des fixtures, utilisez ces identifiants :

| Rôle | Email | Mot de passe |
|------|-------|-------------|
| Administrateur | `admin@erp-architecte.fr` | `admin123` |
| Architecte | `architecte@erp-architecte.fr` | `user123` |

---

## Commandes Makefile utiles

Le projet inclut un `Makefile` avec des raccourcis pour les tâches courantes :

```bash
make help          # Afficher toutes les commandes disponibles
make init          # Initialisation complète (voir étape 4)
make start         # Démarrer les conteneurs
make stop          # Arrêter les conteneurs
make restart       # Redémarrer les conteneurs
make logs          # Afficher les logs en temps réel
make shell         # Ouvrir un shell dans le conteneur app
make db-reset      # Réinitialiser la base de données
make test          # Lancer la suite de tests
make lint          # Vérifier le code (PHP-CS-Fixer + PHPStan)
make assets-watch  # Compiler les assets en mode watch
```

---

## Dépannage

### Les conteneurs ne démarrent pas

```bash
# Vérifier les logs d'un service spécifique
docker compose logs nginx
docker compose logs db
docker compose logs app

# Forcer la reconstruction des images
docker compose up -d --build
```

### Erreur de connexion à la base de données

```bash
# Vérifier que MySQL est prêt
docker compose exec db mysqladmin -u root -proot ping

# Attendre quelques secondes après le premier démarrage
sleep 10 && docker compose exec app php bin/console doctrine:database:create
```

### Port déjà utilisé

Si le port 8080 est déjà utilisé sur votre machine, modifiez `docker-compose.yml` :

```yaml
services:
  nginx:
    ports:
      - "8090:80"   # Changer 8080 en 8090 (ou autre)
```

### Permissions sur les fichiers (Linux)

```bash
# Corriger les permissions du cache Symfony
docker compose exec app chown -R www-data:www-data var/
docker compose exec app chmod -R 755 var/
```

### Réinitialiser complètement l'environnement

```bash
# Arrêter et supprimer tous les conteneurs + volumes
docker compose down -v

# Repartir de zéro
docker compose up -d
make init
```

---

:::tip Mode développement avec hot-reload
Pour le développement avec rechargement automatique des assets :

```bash
# Dans un terminal dédié
docker compose exec app npm run watch
```

Les modifications des fichiers JS/CSS seront compilées automatiquement.
:::
