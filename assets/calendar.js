/**
 * calendar.js
 *
 * Initialises FullCalendar on #calendar.
 * This module is loaded lazily by app.js only when #calendar is present.
 *
 * Expected DOM markup:
 *
 *   <div id="calendar"
 *        data-events-url="/events/api/events"
 *        data-event-base-url="/events">
 *   </div>
 *
 * The server endpoint (data-events-url) must return an array of FullCalendar
 * Event Objects:
 *   [
 *     {
 *       "id": 1,
 *       "title": "Réunion client",
 *       "start": "2024-06-10T09:00:00",
 *       "end":   "2024-06-10T10:30:00",
 *       "color": "#3B82F6",
 *       "extendedProps": { "type": "meeting", "showUrl": "/events/1" }
 *     },
 *     …
 *   ]
 */

import { Calendar } from '@fullcalendar/core';
import dayGridPlugin   from '@fullcalendar/daygrid';
import timeGridPlugin  from '@fullcalendar/timegrid';
import listPlugin      from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';

// ── Colour palette per event type ─────────────────────────────────────────────
const TYPE_COLORS = {
    meeting:     '#3B82F6',
    deadline:    '#EF4444',
    task:        '#10B981',
    reminder:    '#F59E0B',
    visit:       '#8B5CF6',
    default:     '#6B7280',
};

function colorForType(type) {
    return TYPE_COLORS[type] ?? TYPE_COLORS.default;
}

// ── Modal helpers ─────────────────────────────────────────────────────────────
/**
 * If a Bootstrap modal with id="eventModal" is present in the DOM we use it.
 * Otherwise we fall back to navigating to the event's show page.
 */
function openEventModal(eventInfo) {
    const modalEl = document.getElementById('eventModal');

    if (modalEl) {
        // Fill modal fields
        const setField = (id, value) => {
            const el = modalEl.querySelector(`#${id}`);
            if (el) el.textContent = value ?? '—';
        };

        const { event } = eventInfo;
        const ep        = event.extendedProps;

        setField('eventModalTitle',      event.title);
        setField('eventModalStart',      formatDateTime(event.start));
        setField('eventModalEnd',        event.end ? formatDateTime(event.end) : '—');
        setField('eventModalType',       ep.type ?? '—');
        setField('eventModalDescription', ep.description ?? '');

        // "See more" link
        const link = modalEl.querySelector('#eventModalLink');
        if (link && ep.showUrl) {
            link.href    = ep.showUrl;
            link.style.display = 'inline-flex';
        } else if (link) {
            link.style.display = 'none';
        }

        new window.bootstrap.Modal(modalEl).show();
    } else if (eventInfo.event.extendedProps.showUrl) {
        // Hard navigate.
        window.location.href = eventInfo.event.extendedProps.showUrl;
    }
}

function formatDateTime(date) {
    if (!date) return '—';
    return new Intl.DateTimeFormat('fr-FR', {
        weekday:  'long',
        day:      '2-digit',
        month:    'long',
        year:     'numeric',
        hour:     '2-digit',
        minute:   '2-digit',
    }).format(date);
}

// ── Main init ─────────────────────────────────────────────────────────────────
export default function initCalendar() {
    const container = document.getElementById('calendar');
    if (!container) return;

    const eventsUrl    = container.dataset.eventsUrl    || '/events/api/events';
    const eventBaseUrl = container.dataset.eventBaseUrl || '/events';

    const calendar = new Calendar(container, {
        // ── Plugins ───────────────────────────────────────────────────────────
        plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],

        // ── Locale ────────────────────────────────────────────────────────────
        locale:          'fr',
        firstDay:        1,          // Monday
        timeZone:        'local',
        buttonText: {
            today:     "Aujourd'hui",
            month:     'Mois',
            week:      'Semaine',
            day:       'Jour',
            list:      'Liste',
        },

        // ── Toolbar ───────────────────────────────────────────────────────────
        headerToolbar: {
            left:   'prev,next today',
            center: 'title',
            right:  'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
        },

        // ── Initial view ──────────────────────────────────────────────────────
        initialView: 'dayGridMonth',

        // ── Height ────────────────────────────────────────────────────────────
        height:       'auto',
        expandRows:   true,

        // ── Event fetching ────────────────────────────────────────────────────
        events: {
            url:     eventsUrl,
            method:  'GET',
            failure: () => {
                showCalendarError(container);
            },
        },

        // Transform events returned by the API to add colours when not set.
        eventDataTransform: (rawEvent) => {
            if (!rawEvent.color && rawEvent.extendedProps?.type) {
                rawEvent.color = colorForType(rawEvent.extendedProps.type);
            }
            return rawEvent;
        },

        // ── Loading indicator ─────────────────────────────────────────────────
        loading: (isLoading) => {
            const spinner = container.querySelector('.fc-loading-spinner');
            if (spinner) {
                spinner.style.display = isLoading ? 'flex' : 'none';
            }
        },

        // ── Event rendering ───────────────────────────────────────────────────
        eventDidMount: (info) => {
            // Add a Bootstrap tooltip with the event title.
            const tooltip = new window.bootstrap.Tooltip(info.el, {
                title:     info.event.title,
                placement: 'top',
                trigger:   'hover',
                container: 'body',
            });
            // Store instance so it can be destroyed on eventWillUnmount.
            info.el._tooltip = tooltip;
        },

        eventWillUnmount: (info) => {
            if (info.el._tooltip) {
                info.el._tooltip.dispose();
            }
        },

        // ── Interaction ───────────────────────────────────────────────────────
        eventClick: (eventInfo) => {
            openEventModal(eventInfo);
        },

        // Date click — could open a "new event" form.
        dateClick: (info) => {
            const newEventUrl = `${eventBaseUrl}/new?date=${info.dateStr}`;
            const createBtn   = document.getElementById('calendarCreateBtn');
            if (createBtn) {
                createBtn.href = newEventUrl;
            }
        },

        // Drag-and-drop (requires interactionPlugin).
        editable:         false,
        droppable:        false,
        selectable:       false,

        // ── More link ─────────────────────────────────────────────────────────
        dayMaxEvents:     3,
        moreLinkText:     (n) => `+${n} autre${n > 1 ? 's' : ''}`,
        moreLinkClick:    'popover',

        // ── Views customisation ───────────────────────────────────────────────
        views: {
            dayGridMonth: {
                dayMaxEvents: 3,
            },
            timeGridWeek: {
                slotMinTime:          '07:00:00',
                slotMaxTime:          '21:00:00',
                slotDuration:         '00:30:00',
                slotLabelInterval:    '01:00',
                nowIndicator:         true,
                allDaySlot:           true,
                allDayText:           'Journée',
                scrollTime:           '08:00:00',
            },
            timeGridDay: {
                slotMinTime:          '07:00:00',
                slotMaxTime:          '21:00:00',
                slotDuration:         '00:30:00',
                nowIndicator:         true,
                allDaySlot:           true,
                allDayText:           'Journée',
            },
            listWeek: {
                noEventsText:         'Aucun événement cette semaine.',
            },
        },
    });

    calendar.render();

    // Expose for debugging / external control.
    window.__erp_calendar = calendar;

    return calendar;
}

// ── Error handling ────────────────────────────────────────────────────────────
function showCalendarError(container) {
    const errDiv = document.createElement('div');
    errDiv.className = 'alert alert-danger m-3';
    errDiv.innerHTML =
        '<i class="bi bi-exclamation-triangle-fill me-2"></i>' +
        'Impossible de charger les événements. Veuillez rafraîchir la page.';
    container.prepend(errDiv);
}
