---
id: intro
title: Introduction
sidebar_label: Introduction
slug: /
description: Présentation de l'ERP Architecte — solution de gestion complète pour les cabinets d'architecture en France.
---

# ERP Architecte

**ERP Architecte** est une solution de gestion d'entreprise (ERP) complète, conçue spécifiquement pour les **cabinets d'architecture français**. Elle couvre l'ensemble du cycle de vie d'un projet : de la gestion client à la facturation finale, en passant par le suivi du temps, les devis, les dépenses et la planification.

---

## A qui s'adresse cet ERP ?

| Profil | Usage principal |
|--------|----------------|
| **Architectes indépendants** | Gérer clients, projets et facturation depuis une seule interface |
| **Cabinets d'architecture (2–20 personnes)** | Centraliser la gestion des projets, des honoraires et du temps passé |
| **Assistants de gestion** | Suivre les devis, les factures et les relances |
| **Chefs de projet** | Planifier les tâches et visualiser l'avancement sur le calendrier |

---

## Fonctionnalités clés

### Gestion clients et projets
- Annuaire clients avec historique complet (projets, devis, factures)
- Projets avec statut, budget, adresse et responsable
- Suivi des phases de conception : Esquisse, APS, APD, PC, PRO, ACT, EXE, OPC, AOR

### Devis et facturation
- Création de devis depuis un catalogue de prestations
- Conversion devis -> facture en un clic
- Facturation avec TVA, acomptes et situations d'avancement
- Export PDF professionnel

### Suivi du temps
- Saisie des heures par projet et par collaborateur
- Rapport hebdomadaire et mensuel
- Calcul automatique du taux horaire et des honoraires

### Comptabilité des dépenses
- Enregistrement des notes de frais (déplacements, repas, matériaux)
- Rattachement au projet concerné
- Export comptable

### Calendrier et planification
- Calendrier interactif (FullCalendar)
- Visualisation des jalons, réunions et échéances
- Synchronisation avec les projets

### Documents
- Gestion documentaire liée aux projets
- Stockage des plans, rapports et contrats

---

## Apercu de l'application

> **Note :** Les captures d'écran ci-dessous seront ajoutées lors du déploiement.

```
┌─────────────────────────────────────────────────────────────┐
│  [Screenshot placeholder — Dashboard principal]             │
│  Vue d'ensemble : KPIs, projets récents, calendrier         │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  [Screenshot placeholder — Gestion des projets]             │
│  Liste, filtres, statuts et détail d'un projet              │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  [Screenshot placeholder — Création de devis]               │
│  Formulaire multi-lignes avec calcul automatique            │
└─────────────────────────────────────────────────────────────┘
```

---

## Stack technique

| Composant | Technologie |
|-----------|------------|
| **Backend** | Symfony 7 (PHP 8.2) |
| **Base de données** | MySQL 8.0 |
| **Frontend** | Bootstrap 5, Chart.js, FullCalendar, DataTables |
| **Build** | Webpack Encore |
| **Infrastructure** | Docker Compose |
| **Serveur web** | Nginx |

Pour les détails de l'architecture, consultez la section [Stack Technique](/architecture/stack).

---

## Acces rapide

| URL | Service |
|-----|---------|
| `http://localhost:8080` | Application ERP |
| `http://localhost:8081` | phpMyAdmin |
| `http://localhost:8025` | Mailpit (emails dev) |
| `http://localhost:3000` | Cette documentation |

---

## Demarrage rapide

```bash
# 1. Cloner le dépôt
git clone https://github.com/erp-architecte/erp-archi.git
cd erp-archi

# 2. Démarrer les conteneurs
docker compose up -d

# 3. Initialiser l'application
make init

# 4. Ouvrir l'application
open http://localhost:8080
```

Pour une installation complète, suivez le guide [Installation](/getting-started/installation).

---

## Structure du projet

```
erp-archi/
├── src/
│   ├── Controller/        # Contrôleurs Symfony
│   ├── Entity/            # Entités Doctrine (13 entités)
│   ├── Repository/        # Repositories
│   ├── Service/           # Services métier (5 services)
│   ├── Form/              # Types de formulaires
│   └── EventSubscriber/   # Subscribers Symfony
├── templates/             # Templates Twig
├── assets/                # JS/CSS sources
├── migrations/            # Migrations Doctrine
├── docker/                # Configuration Docker
├── docs/                  # Cette documentation
└── Makefile               # Commandes utilitaires
```

---

:::tip Contribuer à la documentation
Cette documentation est générée avec [Docusaurus](https://docusaurus.io/). Les fichiers sources se trouvent dans `docs/docs/`. Les Pull Requests sont les bienvenues.
:::
