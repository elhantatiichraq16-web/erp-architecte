---
id: events-api
title: API Événements (FullCalendar)
sidebar_label: API Événements
description: Documentation de l'endpoint JSON /events/api/events utilisé par FullCalendar dans l'ERP Architecte.
---

# API Événements — FullCalendar

L'ERP Architecte expose un endpoint JSON pour alimenter le calendrier FullCalendar avec les événements du cabinet. Cet endpoint supporte le filtrage par date, projet et type.

---

## Endpoint

```
GET /events/api/events
```

---

## Description

Retourne la liste des événements du calendrier au format JSON compatible **FullCalendar Event Object**. L'endpoint est sécurisé et nécessite une session authentifiée.

---

## Paramètres de requête

| Paramètre | Type | Obligatoire | Description |
|-----------|------|-------------|-------------|
| `start` | `string` | Non | Date de début au format ISO 8601 (`2024-01-01T00:00:00`) |
| `end` | `string` | Non | Date de fin au format ISO 8601 (`2024-01-31T23:59:59`) |
| `project` | `integer` | Non | Filtrer par ID de projet |
| `type` | `string` | Non | Filtrer par type d'événement (`meeting`, `deadline`, `site_visit`, `reminder`) |
| `user` | `integer` | Non | Filtrer par ID d'utilisateur (admin seulement) |

### Exemple de requête

```
GET /events/api/events?start=2024-01-01&end=2024-01-31&project=5&type=meeting
```

---

## Format de réponse

**Content-Type :** `application/json`

**Code HTTP :** `200 OK`

### Structure du tableau JSON

```json
[
  {
    "id": 42,
    "title": "Réunion chantier Villa Dupont",
    "start": "2024-01-15T10:00:00+01:00",
    "end": "2024-01-15T11:30:00+01:00",
    "allDay": false,
    "color": "#3B82F6",
    "textColor": "#ffffff",
    "description": "Point avancement avec l'entreprise générale",
    "location": "15 rue des Lilas, 75015 Paris",
    "type": "meeting",
    "projectId": 5,
    "projectName": "Villa Dupont — Rénovation",
    "userId": 3,
    "userName": "Marie Dubois",
    "url": "/calendar/42/edit",
    "extendedProps": {
      "type": "meeting",
      "projectId": 5,
      "projectName": "Villa Dupont — Rénovation",
      "location": "15 rue des Lilas, 75015 Paris",
      "description": "Point avancement avec l'entreprise générale",
      "userId": 3,
      "userName": "Marie Dubois",
      "editUrl": "/calendar/42/edit",
      "deleteUrl": "/calendar/42"
    }
  },
  {
    "id": 43,
    "title": "Dépôt Permis de Construire",
    "start": "2024-01-20",
    "end": null,
    "allDay": true,
    "color": "#EF4444",
    "textColor": "#ffffff",
    "description": "Dépôt en mairie du dossier PC pour la maison Bertrand",
    "location": "Mairie du 16e arrondissement",
    "type": "deadline",
    "projectId": 8,
    "projectName": "Maison Bertrand — Construction neuve",
    "userId": 1,
    "userName": "Jean Martin",
    "url": "/calendar/43/edit",
    "extendedProps": {
      "type": "deadline",
      "projectId": 8,
      "projectName": "Maison Bertrand — Construction neuve",
      "location": "Mairie du 16e arrondissement",
      "description": "Dépôt en mairie du dossier PC pour la maison Bertrand",
      "userId": 1,
      "userName": "Jean Martin",
      "editUrl": "/calendar/43/edit",
      "deleteUrl": "/calendar/43"
    }
  }
]
```

---

## Champs de la réponse

| Champ | Type | Description |
|-------|------|-------------|
| `id` | `integer` | Identifiant unique de l'événement |
| `title` | `string` | Titre affiché dans le calendrier |
| `start` | `string` | Date/heure de début (ISO 8601 avec timezone) |
| `end` | `string\|null` | Date/heure de fin (`null` si non définie) |
| `allDay` | `boolean` | `true` si l'événement dure toute la journée |
| `color` | `string` | Couleur de fond en hexadécimal (ex: `#3B82F6`) |
| `textColor` | `string` | Couleur du texte (calculée pour le contraste) |
| `description` | `string\|null` | Description détaillée |
| `location` | `string\|null` | Lieu de l'événement |
| `type` | `string` | Type d'événement (voir tableau ci-dessous) |
| `projectId` | `integer\|null` | ID du projet associé |
| `projectName` | `string\|null` | Nom du projet associé |
| `userId` | `integer` | ID du créateur |
| `userName` | `string` | Nom du créateur |
| `url` | `string` | URL de la page de modification |
| `extendedProps` | `object` | Données supplémentaires pour FullCalendar |

### Types d'événements et couleurs

| Type | Couleur | Code couleur |
|------|---------|-------------|
| `meeting` | Bleu | `#3B82F6` |
| `site_visit` | Vert | `#10B981` |
| `deadline` | Rouge | `#EF4444` |
| `reminder` | Orange | `#F59E0B` |
| `permit` | Violet | `#8B5CF6` |
| `other` | Gris | `#6B7280` |

---

## Codes d'erreur

| Code HTTP | Description |
|-----------|-------------|
| `200 OK` | Succès, liste des événements retournée |
| `401 Unauthorized` | Non authentifié — redirection vers la page de connexion |
| `403 Forbidden` | Accès refusé (filtrage par utilisateur non autorisé) |
| `422 Unprocessable Entity` | Paramètres de date invalides |
| `500 Internal Server Error` | Erreur serveur |

**Exemple de réponse d'erreur (422) :**

```json
{
  "error": "Invalid date format",
  "message": "The 'start' parameter must be a valid ISO 8601 date string.",
  "parameter": "start",
  "value": "not-a-date"
}
```

---

## Implementation du controleur

```php
// src/Controller/EventController.php

#[Route('/events/api/events', name: 'events_api', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
public function apiEvents(
    Request $request,
    EventRepository $eventRepository,
): JsonResponse
{
    // Récupération et validation des paramètres
    $startParam = $request->query->get('start');
    $endParam = $request->query->get('end');
    $projectId = $request->query->getInt('project');
    $type = $request->query->get('type');
    $userId = $request->query->getInt('user');

    // Parse les dates
    $start = $startParam ? new \DateTimeImmutable($startParam) : null;
    $end = $endParam ? new \DateTimeImmutable($endParam) : null;

    // Seul un admin peut filtrer par utilisateur
    if ($userId && !$this->isGranted('ROLE_ADMIN')) {
        throw $this->createAccessDeniedException();
    }

    // Si pas d'utilisateur spécifié, retourner les événements de l'utilisateur connecté
    // ou tous les événements pour un admin
    $user = $userId
        ? $this->userRepository->find($userId)
        : ($this->isGranted('ROLE_ADMIN') ? null : $this->getUser());

    // Requête
    $events = $eventRepository->findForCalendar(
        start: $start,
        end: $end,
        projectId: $projectId ?: null,
        type: $type ?: null,
        user: $user,
    );

    // Sérialisation au format FullCalendar
    $data = array_map(fn(EventItem $event) => $this->serializeEvent($event), $events);

    return $this->json($data);
}

private function serializeEvent(EventItem $event): array
{
    $color = match ($event->getType()) {
        'meeting'    => '#3B82F6',
        'site_visit' => '#10B981',
        'deadline'   => '#EF4444',
        'reminder'   => '#F59E0B',
        'permit'     => '#8B5CF6',
        default      => $event->getColor() ?? '#6B7280',
    };

    return [
        'id' => $event->getId(),
        'title' => $event->getTitle(),
        'start' => $event->getStartAt()->format(\DateTimeInterface::ATOM),
        'end' => $event->getEndAt()?->format(\DateTimeInterface::ATOM),
        'allDay' => $event->isAllDay(),
        'color' => $color,
        'textColor' => '#ffffff',
        'description' => $event->getDescription(),
        'location' => $event->getLocation(),
        'type' => $event->getType(),
        'projectId' => $event->getProject()?->getId(),
        'projectName' => $event->getProject()?->getName(),
        'userId' => $event->getUser()->getId(),
        'userName' => $event->getUser()->getFullName(),
        'url' => $this->generateUrl('event_edit', ['id' => $event->getId()]),
        'extendedProps' => [
            'type'        => $event->getType(),
            'projectId'   => $event->getProject()?->getId(),
            'projectName' => $event->getProject()?->getName(),
            'location'    => $event->getLocation(),
            'description' => $event->getDescription(),
            'userId'      => $event->getUser()->getId(),
            'userName'    => $event->getUser()->getFullName(),
            'editUrl'     => $this->generateUrl('event_edit', ['id' => $event->getId()]),
            'deleteUrl'   => $this->generateUrl('event_delete', ['id' => $event->getId()]),
        ],
    ];
}
```

---

## Repository — Méthode findForCalendar

```php
// src/Repository/EventRepository.php

public function findForCalendar(
    ?\DateTimeInterface $start = null,
    ?\DateTimeInterface $end = null,
    ?int $projectId = null,
    ?string $type = null,
    ?User $user = null,
): array
{
    $qb = $this->createQueryBuilder('e')
        ->leftJoin('e.project', 'p')
        ->leftJoin('e.user', 'u')
        ->addSelect('p', 'u')
        ->orderBy('e.startAt', 'ASC');

    if ($start) {
        $qb->andWhere('e.startAt >= :start OR e.endAt >= :start')
           ->setParameter('start', $start);
    }

    if ($end) {
        $qb->andWhere('e.startAt <= :end')
           ->setParameter('end', $end);
    }

    if ($projectId) {
        $qb->andWhere('e.project = :projectId')
           ->setParameter('projectId', $projectId);
    }

    if ($type) {
        $qb->andWhere('e.type = :type')
           ->setParameter('type', $type);
    }

    if ($user) {
        $qb->andWhere('e.user = :user')
           ->setParameter('user', $user);
    }

    return $qb->getQuery()->getResult();
}
```

---

## Utilisation dans FullCalendar

```javascript
// assets/calendar.js

const calendar = new FullCalendar.Calendar(calendarEl, {
    events: {
        url: '/events/api/events',
        method: 'GET',
        extraParams: function() {
            return {
                project: document.getElementById('filter-project')?.value || '',
                type: document.getElementById('filter-type')?.value || '',
            };
        },
        failure: function(error) {
            console.error('Erreur lors du chargement des événements:', error);
            showToast('Impossible de charger les événements.', 'error');
        }
    },

    eventContent: function(arg) {
        // Personnalisation du rendu des événements
        const event = arg.event;
        const props = event.extendedProps;

        return {
            html: `
                <div class="fc-event-main-frame">
                    <div class="fc-event-title-container">
                        <div class="fc-event-title fw-semibold">
                            ${event.title}
                        </div>
                        ${props.projectName ? `
                        <div class="fc-event-subtitle small opacity-75">
                            ${props.projectName}
                        </div>` : ''}
                    </div>
                </div>
            `
        };
    },
});
```

---

## Test de l'endpoint

Vous pouvez tester l'endpoint avec `curl` (depuis une session authentifiée) :

```bash
# Événements du mois de janvier 2024
curl -b "PHPSESSID=your_session_id" \
     "http://localhost:8080/events/api/events?start=2024-01-01&end=2024-01-31"

# Événements d'un projet spécifique
curl -b "PHPSESSID=your_session_id" \
     "http://localhost:8080/events/api/events?project=5"

# Événements de type deadline
curl -b "PHPSESSID=your_session_id" \
     "http://localhost:8080/events/api/events?type=deadline&start=2024-01-01&end=2024-12-31"
```

---

:::info Pagination
L'endpoint ne pagine pas les résultats. FullCalendar interroge uniquement la plage de dates visible, ce qui limite naturellement le nombre d'événements retournés. Pour des calendriers avec de nombreux événements (>1000), envisagez d'ajouter une limite côté serveur.
:::

:::tip CORS
En développement local, l'endpoint est accessible uniquement depuis la même origine (http://localhost:8080). Pour permettre l'accès depuis d'autres origines (Docusaurus, apps externes), ajoutez la configuration CORS via le bundle `nelmio/cors-bundle`.
:::
