---
id: dashboard
title: Tableau de bord
sidebar_label: Dashboard
description: Documentation du module tableau de bord de l'ERP Architecte.
---

# Tableau de bord (Dashboard)

Le tableau de bord est la page d'accueil de l'ERP. Il offre une **vue synthétique** de l'activité du cabinet avec des indicateurs clés, des graphiques et les dernières activités.

---

## Objectif

Permettre à l'architecte ou au gestionnaire de comprendre **en un coup d'oeil** :
- L'état financier du cabinet (CA, factures en attente)
- L'avancement des projets en cours
- Le planning des prochains événements
- Les alertes importantes (factures en retard, devis expirant)

---

## Fonctionnalités

### KPIs (Indicateurs clés)

Quatre cartes de statistiques affichées en haut de page :

| Indicateur | Description | Calcul |
|-----------|-------------|--------|
| **Projets actifs** | Nombre de projets avec statut `in_progress` | `COUNT(projects WHERE status = 'in_progress')` |
| **CA du mois** | Montant total des factures payées ce mois | `SUM(invoices WHERE status = 'paid' AND month = current)` |
| **Factures en attente** | Montant total non encaissé | `SUM(invoices WHERE status IN ('sent', 'overdue'))` |
| **Heures ce mois** | Heures saisies pour le mois courant | `SUM(time_entries WHERE month = current)` |

### Graphiques

**Chiffre d'affaires mensuel (12 mois glissants)**
- Type : Barres verticales (Chart.js)
- Données : CA HT par mois
- Mise à jour : Temps réel à chaque chargement

**Répartition des projets par statut**
- Type : Graphique en anneau (doughnut)
- Données : Comptage par statut (En cours, Terminé, En pause, etc.)

**Évolution des heures par semaine**
- Type : Courbe (line chart)
- Données : Total heures par semaine sur les 8 dernières semaines

### Tableaux récapitulatifs

**Derniers projets** — Les 5 projets les plus récemment modifiés avec :
- Nom, client, statut, progression

**Factures en retard** — Liste des factures impayées dont l'échéance est dépassée avec :
- Numéro, client, montant, jours de retard, bouton de relance

**Prochains événements** — Les 5 prochains événements du calendrier :
- Titre, date, projet associé

### Alertes système

Bandeau d'alertes affiché si :
- Des factures sont en retard de paiement
- Des devis arrivent à expiration dans moins de 7 jours
- Un projet dépasse son budget estimé

---

## Routes

| Méthode | URL | Nom de la route | Description |
|---------|-----|----------------|-------------|
| `GET` | `/` | `dashboard` | Page principale du tableau de bord |
| `GET` | `/dashboard/stats` | `dashboard_stats` | Endpoint AJAX pour rafraîchir les KPIs |

---

## Controleur

```php
// src/Controller/DashboardController.php
#[Route('/', name: 'dashboard')]
public function index(ProjectStatsService $statsService): Response
{
    return $this->render('dashboard/index.html.twig', [
        'stats' => $statsService->getDashboardStats(),
        'revenueByMonth' => $statsService->getRevenueByMonth((int)date('Y')),
        'projectsByStatus' => $statsService->getProjectsByStatus(),
        'recentProjects' => $this->projectRepository->findRecentlyUpdated(5),
        'overdueInvoices' => $this->invoiceRepository->findOverdue(),
        'upcomingEvents' => $this->eventRepository->findUpcoming(5),
        'alerts' => $statsService->getAlerts(),
    ]);
}
```

---

## Template

**Fichier :** `templates/dashboard/index.html.twig`

Structure de la page :

```
dashboard/index.html.twig
├── _kpis.html.twig          (4 cartes KPI)
├── _charts.html.twig        (Graphiques Chart.js)
├── _recent_projects.html.twig
├── _overdue_invoices.html.twig
├── _upcoming_events.html.twig
└── _alerts.html.twig
```

---

## Capture d'ecran

```
┌─────────────────────────────────────────────────────────────────┐
│  [Screenshot placeholder — Dashboard]                            │
│  4 KPI cards + 2 charts + recent projects + overdue invoices     │
└─────────────────────────────────────────────────────────────────┘
```

---

## Options de configuration

| Paramètre | Clé Setting | Valeur par défaut |
|-----------|-------------|-------------------|
| Nombre de projets récents | `dashboard.recent_projects_count` | `5` |
| Nombre d'événements à venir | `dashboard.upcoming_events_count` | `5` |
| Seuil alerte facture (jours) | `dashboard.overdue_alert_days` | `0` |
| Seuil alerte devis (jours) | `dashboard.quote_expiry_warning_days` | `7` |

---

:::info Droits d'accès
Le tableau de bord est accessible à tous les utilisateurs authentifiés (`ROLE_USER`). Les montants financiers peuvent être masqués pour les rôles non-gestionnaires selon la configuration.
:::
