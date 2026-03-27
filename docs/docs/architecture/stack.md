---
id: stack
title: Stack Technique
sidebar_label: Stack Technique
description: Détail complet des technologies utilisées dans l'ERP Architecte.
---

# Stack Technique

L'ERP Architecte est construit sur un ensemble de technologies modernes, stables et éprouvées, choisies pour leur adéquation avec les besoins d'une application métier professionnelle.

---

## Vue d'ensemble

```
┌─────────────────────────────────────────────────────────────────┐
│                        NAVIGATEUR CLIENT                        │
│  Bootstrap 5 · Chart.js · FullCalendar · DataTables · Twig      │
└──────────────────────────────┬──────────────────────────────────┘
                               │ HTTP/HTTPS
┌──────────────────────────────▼──────────────────────────────────┐
│                     NGINX 1.25 (Alpine)                         │
│              Reverse proxy · Gzip · Cache statique              │
└──────────────────────────────┬──────────────────────────────────┘
                               │ FastCGI (PHP-FPM)
┌──────────────────────────────▼──────────────────────────────────┐
│              PHP 8.2 + Symfony 7 (PHP-FPM)                      │
│  Controllers · Services · Forms · Events · Security · Mailer    │
│              Doctrine ORM · Twig · Webpack Encore               │
└──────────────────────────────┬──────────────────────────────────┘
                               │ PDO MySQL
┌──────────────────────────────▼──────────────────────────────────┐
│                      MySQL 8.0                                   │
│              Données métier · Transactions · JSON                │
└─────────────────────────────────────────────────────────────────┘
```

---

## Backend

### Symfony 7

| Caractéristique | Détail |
|----------------|--------|
| **Version** | 7.x (LTS) |
| **PHP requis** | 8.2+ |
| **Architecture** | MVC avec injection de dépendances |
| **Routing** | Annotations PHP + YAML |
| **Security** | Voters, Roles, Firewalls |
| **Forms** | Symfony Form Component |
| **Validation** | Symfony Validator (annotations) |
| **Events** | EventDispatcher, Subscribers |

**Bundles Symfony utilisés :**

| Bundle | Rôle |
|--------|------|
| `doctrine/doctrine-bundle` | ORM et gestion BDD |
| `doctrine/doctrine-migrations-bundle` | Migrations de schéma |
| `symfony/security-bundle` | Authentification et autorisations |
| `symfony/mailer` | Envoi d'emails |
| `symfony/twig-bundle` | Moteur de templates |
| `symfony/webpack-encore-bundle` | Intégration Webpack |
| `symfony/validator` | Validation des données |
| `knplabs/knp-paginator-bundle` | Pagination |
| `vich/uploader-bundle` | Upload de fichiers |
| `dompdf/dompdf` | Génération PDF |

---

### PHP 8.2

Fonctionnalités PHP 8.2+ utilisées dans le projet :

```php
// Enums typés (PHP 8.1+)
enum ProjectStatus: string {
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Archived = 'archived';
}

// Readonly properties (PHP 8.1+)
class InvoiceDTO {
    public function __construct(
        public readonly int $id,
        public readonly string $number,
        public readonly float $amount,
    ) {}
}

// Named arguments (PHP 8.0+)
$invoice = new Invoice(
    amount: 5000.00,
    vatRate: 20,
    client: $client,
);

// Fibers (PHP 8.1+) — pour les traitements asynchrones futurs
// Intersection types (PHP 8.1+)
function process(Countable&Iterator $collection): void { ... }
```

---

### Doctrine ORM

```yaml
# Configuration Doctrine (config/packages/doctrine.yaml)
doctrine:
  dbal:
    driver: pdo_mysql
    server_version: '8.0'
    charset: utf8mb4
  orm:
    auto_generate_proxy_classes: true
    naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
    auto_mapping: true
    mappings:
      App:
        type: attribute
        dir: '%kernel.project_dir%/src/Entity'
        prefix: 'App\Entity'
```

**Fonctionnalités Doctrine utilisées :**

- Mapping via **attributs PHP 8** (annotations modernes)
- **Relations** : OneToMany, ManyToMany, ManyToOne, OneToOne
- **Lifecycle Callbacks** : `#[PrePersist]`, `#[PreUpdate]`
- **Custom Repository Methods** avec QueryBuilder
- **Migrations** automatiques via `doctrine:migrations:diff`

---

## Frontend

### Bootstrap 5

| Caractéristique | Détail |
|----------------|--------|
| **Version** | 5.3.x |
| **Thème** | Customisé via SCSS variables |
| **Icônes** | Bootstrap Icons 1.11 |
| **Grille** | 12 colonnes, breakpoints: xs/sm/md/lg/xl/xxl |

**Composants utilisés :**

- Navbar, Sidebar, Breadcrumb
- Cards, Tables, Modals, Offcanvas
- Forms, Input Groups, Validation feedback
- Badges, Alerts, Toast notifications
- Dropdowns, Collapse, Tabs

---

### Chart.js

Utilisé dans le **Dashboard** pour les visualisations :

```javascript
// Exemple : Graphique des revenus mensuels
const ctx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun'],
        datasets: [{
            label: 'Chiffre d\'affaires (€)',
            data: [12000, 19000, 8000, 25000, 22000, 30000],
            backgroundColor: 'rgba(59, 130, 246, 0.8)',
            borderColor: '#3B82F6',
            borderWidth: 2,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { mode: 'index' }
        }
    }
});
```

**Graphiques présents :**
- CA mensuel (barres)
- Répartition par type de projet (doughnut)
- Évolution des heures (ligne)
- Taux de recouvrement des factures (jauge)

---

### FullCalendar

Utilisé dans le module **Calendrier** :

```javascript
const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    locale: 'fr',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,listWeek'
    },
    events: '/events/api/events',   // Endpoint JSON
    eventClick: function(info) {
        // Ouvrir le modal de détail
        showEventModal(info.event);
    },
    dateClick: function(info) {
        // Créer un événement
        openCreateForm(info.dateStr);
    }
});
```

---

### DataTables

Utilisé pour toutes les listes paginées et filtrables :

```javascript
$('#projectsTable').DataTable({
    language: { url: '/datatables/fr-FR.json' },
    pageLength: 15,
    responsive: true,
    order: [[0, 'desc']],
    columnDefs: [
        { orderable: false, targets: [-1] }  // Colonne Actions
    ],
    dom: '<"d-flex justify-content-between"lf>rtip',
});
```

---

### Webpack Encore

```javascript
// webpack.config.js
const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .addEntry('app', './assets/app.js')
    .addEntry('dashboard', './assets/dashboard.js')
    .addEntry('calendar', './assets/calendar.js')
    .enableSassLoader()
    .enablePostCssLoader()
    .enableVersioning(Encore.isProduction())
    .enableSourceMaps(!Encore.isProduction())
    .splitEntryChunks()
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .autoProvidejQuery();
```

---

## Infrastructure

### Docker

| Image | Version | Rôle |
|-------|---------|------|
| `php:8.2-fpm-alpine` | 8.2 | Runtime PHP |
| `nginx:1.25-alpine` | 1.25 | Serveur web |
| `mysql:8.0` | 8.0 | Base de données |
| `phpmyadmin:5.2` | 5.2 | Admin BDD |
| `axllent/mailpit` | latest | SMTP dev |

**Dockerfile PHP :**

```dockerfile
FROM php:8.2-fpm-alpine

# Extensions PHP requises
RUN docker-php-ext-install pdo pdo_mysql opcache intl zip

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Node.js (pour Webpack Encore)
RUN apk add --no-cache nodejs npm

WORKDIR /var/www/html

USER www-data
```

---

## Matrice de compatibilité

| Technologie | Version testée | Version minimale |
|------------|---------------|-----------------|
| PHP | 8.2.x | 8.2 |
| Symfony | 7.1.x | 7.0 |
| MySQL | 8.0.x | 8.0 |
| Node.js | 20.x LTS | 18.x |
| Docker Engine | 24.x | 23.x |
| Docker Compose | 2.20.x | 2.0 |

---

## Choix techniques

### Pourquoi Symfony 7 ?

- Framework PHP le plus mature et le mieux documenté
- Composants réutilisables (Form, Security, Mailer, Validator)
- Performances excellentes avec OPCache
- Long Term Support (LTS)
- Conformité RGPD facilitée par le composant Security

### Pourquoi MySQL 8 ?

- Support JSON natif (pour les configurations dynamiques)
- Window functions (pour les rapports comptables)
- Performances optimisées pour les requêtes OLTP
- Réplication native pour la production

### Pourquoi Bootstrap 5 ?

- Pas de dépendance jQuery (Bootstrap 5 est vanilla JS)
- Composants riches adaptés aux applications métier
- Thémisation facile via variables SCSS
- Responsive par défaut
