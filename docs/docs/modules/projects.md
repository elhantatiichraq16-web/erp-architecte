---
id: projects
title: Gestion des Projets
sidebar_label: Projets
description: Documentation du module de gestion des projets de l'ERP Architecte.
---

# Gestion des Projets

Le module Projets est le coeur de l'ERP. Il centralise toutes les informations d'un projet d'architecture : mission, budget, phases, temps passé, documents et facturation.

---

## Objectif

Donner une vision complète de chaque projet d'architecture :
- Suivre l'avancement des phases de conception
- Contrôler le budget et les honoraires
- Centraliser tous les documents liés
- Consulter le temps passé et la rentabilité

---

## Fonctionnalites

### Liste des projets

- Vue tableau et vue cartes (toggle)
- Filtres : statut, client, année, type de mission
- Recherche par référence, nom ou adresse
- Indicateur d'avancement (barre de progression)
- Tri multi-colonnes

### Fiche projet

**En-tête :**
- Référence, nom, client, statut (badge coloré)
- Barre de progression des phases
- Actions rapides : créer devis, saisir du temps, ajouter document

**Onglet Vue d'ensemble**
- Informations générales : adresse, surface, dates
- Résumé financier : budget, honoraires, facturé, encaissé
- Phases : tableau des phases avec avancement

**Onglet Temps**
- Tableau des saisies de temps avec filtres
- Graphique heures par collaborateur
- Total heures / heures facturables / montant facturable
- Bouton ajout rapide de temps

**Onglet Devis**
- Historique des devis liés au projet
- Statuts et montants

**Onglet Factures**
- Toutes les factures du projet
- Montant facturé total vs encaissé

**Onglet Dépenses**
- Notes de frais liées au projet
- Total dépenses par catégorie

**Onglet Documents**
- Fichiers joints (plans, contrats, rapports)
- Upload de nouveaux documents
- Téléchargement et prévisualisation

**Onglet Calendrier**
- Événements liés à ce projet
- Formulaire d'ajout rapide d'événement

### Suivi des phases

Chaque projet peut être découpé en phases standard :

| Phase | Code | Honoraires typiques |
|-------|------|---------------------|
| Esquisse | ESQ | 3–5% |
| Avant-Projet Sommaire | APS | 8–10% |
| Avant-Projet Définitif | APD | 12–15% |
| Permis de Construire | PC | 5–8% |
| Projet | PRO | 15–20% |
| Assistance Contrats Travaux | ACT | 5–8% |
| Études d'exécution | EXE | 15–20% |
| OPC | OPC | 5–10% |
| Assistance Opérations Réception | AOR | 3–5% |

### Référence automatique

Les références sont générées automatiquement au format `PRJ-YYYY-NNN` :
- `PRJ-2024-001` — Premier projet de 2024
- `PRJ-2024-042` — 42ème projet de 2024

---

## Routes

| Méthode | URL | Nom de la route | Description |
|---------|-----|----------------|-------------|
| `GET` | `/projects` | `project_index` | Liste des projets |
| `GET` | `/projects/new` | `project_new` | Formulaire de création |
| `POST` | `/projects/new` | `project_new` | Créer un projet |
| `GET` | `/projects/{id}` | `project_show` | Fiche projet |
| `GET` | `/projects/{id}/edit` | `project_edit` | Modifier un projet |
| `POST` | `/projects/{id}/edit` | `project_edit` | Sauvegarder les modifications |
| `POST` | `/projects/{id}/status` | `project_change_status` | Changer le statut (AJAX) |
| `GET` | `/projects/{id}/export` | `project_export_pdf` | Export fiche projet PDF |
| `POST` | `/projects/{id}/delete` | `project_delete` | Archiver le projet |

---

## Formulaire de création

```php
class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Intitulé du projet'])
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'fullName',
                'placeholder' => 'Sélectionner un client',
                'query_builder' => fn($repo) => $repo->createQueryBuilder('c')
                    ->where('c.isActive = true')
                    ->orderBy('c.lastName', 'ASC'),
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de mission',
                'choices' => [
                    'Construction neuve' => 'new_build',
                    'Rénovation' => 'renovation',
                    'Extension' => 'extension',
                    'Intérieur' => 'interior',
                    'Concours' => 'competition',
                    'Étude de faisabilité' => 'feasibility',
                ],
            ])
            ->add('status', ChoiceType::class, [...])
            ->add('budget', MoneyType::class, ['currency' => 'EUR', 'required' => false])
            ->add('fee', MoneyType::class, ['currency' => 'EUR', 'required' => false])
            ->add('startDate', DateType::class, ['widget' => 'single_text'])
            ->add('endDate', DateType::class, ['widget' => 'single_text', 'required' => false])
            ->add('address', TextType::class, ['required' => false])
            ->add('city', TextType::class, ['required' => false])
            ->add('postalCode', TextType::class, ['required' => false])
            ->add('surface', NumberType::class, ['required' => false])
            ->add('description', TextareaType::class, ['required' => false]);
    }
}
```

---

## Capture d'ecran

```
┌─────────────────────────────────────────────────────────────────┐
│  [Screenshot placeholder — Liste des projets (vue tableau)]      │
│  Filtres statut + client, tableau avec progression               │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  [Screenshot placeholder — Fiche projet]                         │
│  En-tête + onglets : Vue d'ensemble / Temps / Devis / Factures   │
└─────────────────────────────────────────────────────────────────┘
```

---

## Options de configuration

| Paramètre | Clé Setting | Valeur par défaut |
|-----------|-------------|-------------------|
| Préfixe des références | `projects.reference_prefix` | `PRJ` |
| Vue par défaut | `projects.default_view` | `table` |
| Éléments par page | `projects.per_page` | `15` |
| Types de mission | `projects.mission_types` | *(liste JSON)* |

---

:::tip Duplication de projet
Pour créer un projet similaire à un projet existant, utilisez le bouton "Dupliquer" dans le menu Actions de la fiche projet. Toutes les informations sont copiées (sauf les documents et saisies de temps) avec un nouveau numéro de référence.
:::
