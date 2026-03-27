---
id: calendar
title: Calendrier
sidebar_label: Calendrier
description: Documentation du module calendrier de l'ERP Architecte avec FullCalendar.
---

# Calendrier

Le module Calendrier offre une visualisation interactive de tous les événements du cabinet : réunions, visites de chantier, échéances, jalons de projet, etc. Il est basé sur la bibliothèque **FullCalendar**.

---

## Objectif

- Visualiser l'ensemble des événements du cabinet sur un calendrier interactif
- Créer et modifier des événements directement sur le calendrier
- Filtrer par projet, par type ou par collaborateur
- Synchroniser les échéances des projets avec le calendrier

---

## Types d'evenements

| Type | Couleur | Icône | Description |
|------|---------|-------|-------------|
| `meeting` | Bleu | 📅 | Réunion (client, partenaire, équipe) |
| `site_visit` | Vert | 🏗️ | Visite de chantier |
| `deadline` | Rouge | ⚠️ | Échéance importante |
| `reminder` | Orange | 🔔 | Rappel / tâche à faire |
| `permit` | Violet | 📋 | Dépôt ou retrait de permis |
| `other` | Gris | 📌 | Autre événement |

---

## Fonctionnalites

### Vues disponibles

- **Mois** : Vue mensuelle classique (`dayGridMonth`)
- **Semaine** : Vue semaine avec heures (`timeGridWeek`)
- **Jour** : Vue journalière détaillée (`timeGridDay`)
- **Liste** : Vue liste des prochains événements (`listWeek`)

### Interactions

- **Clic sur un jour** : Créer un événement à cette date
- **Clic sur un événement** : Afficher le détail (popover) avec actions
- **Glisser-déposer** : Déplacer un événement
- **Redimensionner** : Modifier la durée d'un événement en glissant son bord

### Filtres

- Par projet (menu déroulant)
- Par type d'événement (checkboxes colorées)
- Par collaborateur (si `ROLE_ADMIN`)

### Création rapide

Modal de création accessible depuis :
1. Le clic sur une date du calendrier
2. Le bouton "+ Nouvel événement" en haut de page

Champs du formulaire :
- Titre
- Type d'événement
- Date et heure de début / fin
- Option "Toute la journée"
- Projet associé (optionnel)
- Lieu
- Description
- Couleur personnalisée

---

## Routes

| Méthode | URL | Nom de la route | Description |
|---------|-----|----------------|-------------|
| `GET` | `/calendar` | `calendar_index` | Page principale du calendrier |
| `GET` | `/calendar/new` | `event_new` | Formulaire de création |
| `POST` | `/calendar/new` | `event_new` | Créer un événement |
| `GET` | `/calendar/{id}/edit` | `event_edit` | Modifier un événement |
| `POST` | `/calendar/{id}/edit` | `event_edit` | Sauvegarder les modifications |
| `POST` | `/calendar/{id}/move` | `event_move` | Déplacer (drag & drop — AJAX) |
| `POST` | `/calendar/{id}/resize` | `event_resize` | Redimensionner (AJAX) |
| `DELETE` | `/calendar/{id}` | `event_delete` | Supprimer un événement |
| `GET` | `/events/api/events` | `events_api` | API JSON pour FullCalendar |

Pour la documentation détaillée de l'endpoint `/events/api/events`, consultez la [Référence API](/api/events-api).

---

## Integration FullCalendar

```javascript
// assets/calendar.js
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import frLocale from '@fullcalendar/core/locales/fr';

const calendarEl = document.getElementById('calendar');

const calendar = new Calendar(calendarEl, {
    plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    locale: frLocale,
    height: 'auto',

    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
    },

    // Chargement des événements depuis l'API
    events: {
        url: '/events/api/events',
        extraParams: () => ({
            project: document.getElementById('filter-project')?.value || '',
            type: document.getElementById('filter-type')?.value || '',
        }),
    },

    // Créer un événement en cliquant sur une date
    dateClick: function(info) {
        openCreateModal(info.dateStr);
    },

    // Voir / modifier en cliquant sur un événement
    eventClick: function(info) {
        openEventModal(info.event);
    },

    // Glisser-déposer
    editable: true,
    eventDrop: function(info) {
        moveEvent(info.event.id, info.event.startStr, info.event.endStr);
    },

    // Redimensionner
    eventResize: function(info) {
        resizeEvent(info.event.id, info.event.startStr, info.event.endStr);
    },
});

calendar.render();
```

---

## Capture d'ecran

```
┌─────────────────────────────────────────────────────────────────┐
│  [Screenshot placeholder — Calendrier vue mois]                  │
│  Événements colorés par type, filtres projet/type               │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  [Screenshot placeholder — Modal détail événement]               │
│  Titre, type, dates, projet associé, boutons Modifier/Supprimer  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Options de configuration

| Paramètre | Clé Setting | Valeur par défaut |
|-----------|-------------|-------------------|
| Vue par défaut | `calendar.default_view` | `dayGridMonth` |
| Premier jour de la semaine | `calendar.first_day` | `1` (Lundi) |
| Heure de début (vue semaine) | `calendar.slot_min_time` | `08:00` |
| Heure de fin (vue semaine) | `calendar.slot_max_time` | `20:00` |
| Créneaux de 15 min | `calendar.slot_duration` | `00:30:00` |

---

:::tip Synchronisation iCal
Une fonctionnalité d'export au format iCal (`.ics`) permettra dans une version future de synchroniser le calendrier ERP avec Google Calendar, Outlook ou Apple Calendar.
:::
