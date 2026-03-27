/*
 * Main application entry point.
 * Imports global styles, Bootstrap JS, and initialises all UI modules.
 */

// ── Styles ────────────────────────────────────────────────────────────────────
import './styles/app.scss';

// ── Bootstrap (full bundle including Popper) ──────────────────────────────────
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

// ── DataTables ────────────────────────────────────────────────────────────────
import DataTable from 'datatables.net-bs5';
import 'datatables.net-responsive-bs5';

/**
 * Auto-initialise every table that carries the `.datatable` class.
 * Called on DOMContentLoaded so the DOM is ready.
 */
function initDataTables() {
    document.querySelectorAll('table.datatable').forEach((table) => {
        // Skip tables that have already been initialised to avoid double-init.
        if (DataTable.isDataTable(table)) {
            return;
        }

        new DataTable(table, {
            // Responsive layout handled by the responsive extension.
            responsive: true,
            // French locale strings.
            language: {
                url: '//cdn.datatables.net/plug-ins/2.0.8/i18n/fr-FR.json',
            },
            // Sensible defaults for an ERP.
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            order: [],            // Keep the server-side row order by default.
            dom:
                "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        });
    });
}

// ── Bootstrap Toasts – auto-dismiss ───────────────────────────────────────────
function initToasts() {
    const toastElements = document.querySelectorAll('.toast');
    toastElements.forEach((toastEl) => {
        // bootstrap.bundle already loaded, so window.bootstrap is available.
        const toast = new window.bootstrap.Toast(toastEl, {
            autohide: true,
            delay: 4500,
        });
        toast.show();
    });
}

// ── Dashboard charts (lazy-loaded only when the canvas is present) ────────────
async function initDashboard() {
    const canvas = document.getElementById('chartCA');
    if (!canvas) {
        return; // Not a dashboard page — nothing to do.
    }

    try {
        const { default: initCharts } = await import('./dashboard.js');
        initCharts();
    } catch (err) {
        console.error('[ERP] Failed to load dashboard module:', err);
    }
}

// ── FullCalendar (lazy-loaded only when the calendar container is present) ────
async function initCalendar() {
    const container = document.getElementById('calendar');
    if (!container) {
        return; // Not a calendar page — nothing to do.
    }

    try {
        const { default: initCal } = await import('./calendar.js');
        initCal();
    } catch (err) {
        console.error('[ERP] Failed to load calendar module:', err);
    }
}

// ── Invoice / Quote CollectionType line management ────────────────────────────
async function initInvoiceLines() {
    // The add-line button is the reliable indicator that the form is on-page.
    const addBtn = document.querySelector('[data-collection-add]');
    if (!addBtn) {
        return; // Not an invoice/quote form page — nothing to do.
    }

    try {
        const { default: initLines } = await import('./invoice-lines.js');
        initLines();
    } catch (err) {
        console.error('[ERP] Failed to load invoice-lines module:', err);
    }
}

// ── Bootstrap sidebar toggle (mobile) ────────────────────────────────────────
function initSidebarToggle() {
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar   = document.querySelector('.sidebar');

    if (!toggleBtn || !sidebar) {
        return;
    }

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('show');
    });

    // Close sidebar when clicking outside on mobile.
    document.addEventListener('click', (e) => {
        if (
            window.innerWidth < 992 &&
            !sidebar.contains(e.target) &&
            !toggleBtn.contains(e.target) &&
            sidebar.classList.contains('show')
        ) {
            sidebar.classList.remove('show');
        }
    });
}

// ── Bootstrap offcanvas sidebar (alternative pattern) ────────────────────────
function initOffcanvas() {
    document.querySelectorAll('[data-bs-toggle="offcanvas"]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const target = document.querySelector(trigger.dataset.bsTarget);
            if (target) {
                const oc = new window.bootstrap.Offcanvas(target);
                oc.toggle();
            }
        });
    });
}

// ── Tooltips ──────────────────────────────────────────────────────────────────
function initTooltips() {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        new window.bootstrap.Tooltip(el);
    });
}

// ── Popovers ──────────────────────────────────────────────────────────────────
function initPopovers() {
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach((el) => {
        new window.bootstrap.Popover(el);
    });
}

// ── Boot ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    initDataTables();
    initToasts();
    initSidebarToggle();
    initOffcanvas();
    initTooltips();
    initPopovers();

    // Async modules (no await needed — they fire-and-forget safely).
    initDashboard();
    initCalendar();
    initInvoiceLines();
});
