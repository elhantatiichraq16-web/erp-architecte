---
id: time-tracking
title: Suivi du Temps
sidebar_label: Suivi du temps
description: Documentation du module de suivi du temps de l'ERP Architecte.
---

# Suivi du Temps

Le module Suivi du Temps permet aux collaborateurs d'enregistrer le temps passé sur chaque projet et chaque phase de mission. Il génère des rapports détaillés et permet de calculer les honoraires à facturer.

---

## Objectif

- Enregistrer précisément le temps passé sur chaque projet
- Distinguer les heures facturables des heures non facturables
- Générer des rapports hebdomadaires et mensuels
- Calculer les honoraires en fonction du taux horaire
- Comparer le temps estimé vs le temps réel

---

## Fonctionnalites

### Saisie rapide du temps

Interface de saisie simplifiée :
- Sélection du projet (auto-complétion)
- Sélection de la phase (ESQ, APS, APD, etc.)
- Date (défaut : aujourd'hui)
- Durée en heures décimales (ex: `2.5` = 2h30)
- Description libre de la tâche
- Case à cocher : Heures facturables

### Vue semaine

Tableau de bord hebdomadaire :
- Grille 7 jours × projets
- Saisie directement dans la grille (interface tableur)
- Total d'heures par jour et par projet
- Indicateur objectif hebdomadaire (ex: 35h ou 39h)

### Rapports

**Rapport hebdomadaire :**
- Total heures, heures facturables, heures non facturables
- Répartition par projet (graphique circulaire)
- Répartition par phase
- Export PDF ou CSV

**Rapport mensuel :**
- Évolution des heures par semaine (graphique barres)
- Top 5 projets par temps passé
- Taux de facturation (heures facturables / total)
- Comparaison avec le mois précédent

**Rapport par projet :**
- Heures par collaborateur
- Heures par phase
- Évolution semaine par semaine
- Budget temps initial vs consommé

### Gestion des taux horaires

- Taux horaire défini par utilisateur (profil)
- Possibilité de surcharger le taux par saisie
- Calcul automatique du montant facturable
- Rapport de valorisation des heures

---

## Routes

| Méthode | URL | Nom de la route | Description |
|---------|-----|----------------|-------------|
| `GET` | `/time-tracking` | `time_index` | Tableau de bord temps |
| `GET` | `/time-tracking/new` | `time_new` | Formulaire de saisie |
| `POST` | `/time-tracking/new` | `time_new` | Enregistrer une saisie |
| `GET` | `/time-tracking/{id}/edit` | `time_edit` | Modifier une saisie |
| `POST` | `/time-tracking/{id}/edit` | `time_edit` | Sauvegarder les modifications |
| `DELETE` | `/time-tracking/{id}` | `time_delete` | Supprimer une saisie |
| `GET` | `/time-tracking/week` | `time_week` | Vue semaine |
| `GET` | `/time-tracking/report` | `time_report` | Rapports |
| `GET` | `/time-tracking/export` | `time_export` | Export CSV |

---

## Formulaire de saisie

```php
class TimeEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('project', EntityType::class, [
                'class' => Project::class,
                'choice_label' => fn($p) => sprintf('[%s] %s', $p->getReference(), $p->getName()),
                'placeholder' => 'Sélectionner un projet',
                'query_builder' => fn($repo) => $repo->findActiveQueryBuilder(),
                'attr' => ['class' => 'select2'],
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'data' => new \DateTimeImmutable(),
            ])
            ->add('duration', NumberType::class, [
                'label' => 'Durée (heures)',
                'attr' => [
                    'step' => '0.25',
                    'min' => '0.25',
                    'max' => '24',
                    'placeholder' => 'Ex: 2.5',
                ],
            ])
            ->add('phase', ChoiceType::class, [
                'choices' => ProjectPhase::getChoices(),
                'required' => false,
                'placeholder' => 'Phase (optionnel)',
            ])
            ->add('description', TextareaType::class, ['required' => false])
            ->add('isBillable', CheckboxType::class, [
                'label' => 'Heures facturables',
                'data' => true,
                'required' => false,
            ])
            ->add('hourlyRate', MoneyType::class, [
                'required' => false,
                'label' => 'Taux horaire (laisser vide pour utiliser le taux par défaut)',
            ]);
    }
}
```

---

## Capture d'ecran

```
┌─────────────────────────────────────────────────────────────────┐
│  [Screenshot placeholder — Formulaire saisie de temps]           │
│  Sélection projet + date + durée + phase + description           │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  [Screenshot placeholder — Rapport mensuel]                      │
│  Graphiques + tableau récapitulatif par projet                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## Options de configuration

| Paramètre | Clé Setting | Valeur par défaut |
|-----------|-------------|-------------------|
| Taux horaire par défaut | `time.default_hourly_rate` | `80` |
| Objectif hebdomadaire (h) | `time.weekly_target` | `35` |
| Incrément durée minimum | `time.min_duration` | `0.25` (15 min) |
| Phases disponibles | `time.phases` | *(liste JSON)* |

---

:::tip Saisie en masse
Pour saisir plusieurs jours en une seule fois, utilisez la **vue semaine** qui permet de remplir un tableau de type "feuille de temps" avec une ligne par projet et une colonne par jour.
:::
