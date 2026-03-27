/**
 * invoice-lines.js
 *
 * Manages Symfony CollectionType for invoice / quote line items.
 * This module is loaded lazily by app.js only when [data-collection-add] exists.
 *
 * ── Required HTML structure ───────────────────────────────────────────────────
 *
 *   <!-- The CollectionType wrapper rendered by Symfony -->
 *   <div id="invoice-lines-container"
 *        data-prototype="…"           {# {{ form.lines.vars.prototype|e('html_attr') }} #}
 *        data-index="{{ form.lines|length }}"
 *        data-allow-add="1"
 *        data-allow-delete="1">
 *
 *     {% for line in form.lines %}
 *       <div class="invoice-line-row" data-line-index="{{ loop.index0 }}">
 *         …rendered fields…
 *         <button type="button" class="btn btn-sm btn-outline-danger"
 *                 data-collection-remove>
 *           <i class="bi bi-trash"></i>
 *         </button>
 *       </div>
 *     {% endfor %}
 *   </div>
 *
 *   <!-- "Add line" trigger -->
 *   <button type="button" class="btn btn-outline-primary btn-sm"
 *           data-collection-add
 *           data-target="#invoice-lines-container">
 *     <i class="bi bi-plus-lg me-1"></i> Ajouter une ligne
 *   </button>
 *
 *   <!-- Running totals (optional, auto-updated) -->
 *   <span id="totalHT">0,00 €</span>
 *   <span id="totalTVA">0,00 €</span>
 *   <span id="totalTTC">0,00 €</span>
 *
 * ── Field naming convention ───────────────────────────────────────────────────
 * Each line row must contain inputs with names matching the Symfony pattern, e.g.:
 *   invoice[lines][0][designation]
 *   invoice[lines][0][quantity]
 *   invoice[lines][0][unitPrice]
 *   invoice[lines][0][vatRate]   (optional, defaults to 20)
 *   invoice[lines][0][lineTotal] (read-only display, auto-calculated)
 */

// ── Formatters ────────────────────────────────────────────────────────────────
const EUR = new Intl.NumberFormat('fr-FR', {
    style:                 'currency',
    currency:              'EUR',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

function parseFloat2(str) {
    // Accept both comma and dot as decimal separator.
    const val = parseFloat(String(str).replace(',', '.'));
    return isNaN(val) ? 0 : val;
}

// ── Per-line calculation ──────────────────────────────────────────────────────
/**
 * Recalculate the line total for a single row.
 * @param {HTMLElement} row  – .invoice-line-row element
 */
function calcLineTotal(row) {
    const qtyInput   = row.querySelector('[data-field="quantity"], input[name*="[quantity]"]');
    const priceInput = row.querySelector('[data-field="unitPrice"], input[name*="[unitPrice]"], input[name*="[unit_price]"]');
    const vatInput   = row.querySelector('[data-field="vatRate"], input[name*="[vatRate]"], select[name*="[vatRate]"], input[name*="[vat_rate]"], select[name*="[vat_rate]"]');
    const totalEl    = row.querySelector('[data-field="lineTotal"], input[name*="[lineTotal]"], .line-total-display');

    const qty   = parseFloat2(qtyInput?.value   ?? 0);
    const price = parseFloat2(priceInput?.value ?? 0);
    const vat   = parseFloat2(vatInput?.value   ?? 20);

    const lineHT  = qty * price;
    const lineTTC = lineHT * (1 + vat / 100);

    if (totalEl) {
        if (totalEl.tagName === 'INPUT') {
            totalEl.value = lineHT.toFixed(2);
        } else {
            totalEl.textContent = EUR.format(lineHT);
        }
    }

    // Store computed values on the row for the global totals pass.
    row.dataset.lineHt  = lineHT.toFixed(6);
    row.dataset.lineTtc = lineTTC.toFixed(6);
    row.dataset.lineVat = (lineTTC - lineHT).toFixed(6);
}

// ── Global totals ─────────────────────────────────────────────────────────────
function updateTotals(container) {
    let ht  = 0;
    let tva = 0;
    let ttc = 0;

    container.querySelectorAll('.invoice-line-row').forEach((row) => {
        ht  += parseFloat(row.dataset.lineHt  || 0);
        tva += parseFloat(row.dataset.lineVat || 0);
        ttc += parseFloat(row.dataset.lineTtc || 0);
    });

    const set = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = EUR.format(value);
    };

    set('totalHT',  ht);
    set('totalTVA', tva);
    set('totalTTC', ttc);

    // Also update hidden inputs if present (for server-side totals).
    const setInput = (id, value) => {
        const el = document.getElementById(id);
        if (el && el.tagName === 'INPUT') el.value = value.toFixed(2);
    };

    setInput('totalHTInput',  ht);
    setInput('totalTVAInput', tva);
    setInput('totalTTCInput', ttc);
}

// ── Attach per-row event listeners ───────────────────────────────────────────
/**
 * Attach change listeners to qty/price/vat inputs within a row so that the
 * line total recalculates on every keystroke.
 */
function attachRowListeners(row, container) {
    const triggerFields = row.querySelectorAll(
        '[data-field="quantity"], [data-field="unitPrice"], [data-field="vatRate"],' +
        'input[name*="[quantity]"], input[name*="[unitPrice]"], input[name*="[unit_price]"],' +
        'input[name*="[vatRate]"], select[name*="[vatRate]"],' +
        'input[name*="[vat_rate]"], select[name*="[vat_rate]"]'
    );

    triggerFields.forEach((field) => {
        field.addEventListener('input',  () => { calcLineTotal(row); updateTotals(container); });
        field.addEventListener('change', () => { calcLineTotal(row); updateTotals(container); });
    });

    // Remove button inside the row (if allowed).
    const removeBtn = row.querySelector('[data-collection-remove]');
    if (removeBtn) {
        removeBtn.addEventListener('click', () => {
            row.classList.add('animate__animated', 'animate__fadeOut', 'animate__faster');
            row.addEventListener('animationend', () => {
                row.remove();
                reindexRows(container);
                updateTotals(container);
            }, { once: true });
        });
    }

    // Initial calculation for pre-filled rows (edit form).
    calcLineTotal(row);
}

// ── Re-index rows after removal ───────────────────────────────────────────────
/**
 * After removing a row the visual numbering (1, 2, 3…) is updated.
 * The actual form field names are already correct because the prototype index
 * does not need to be sequential — Symfony uses them as array keys.
 */
function reindexRows(container) {
    container.querySelectorAll('.invoice-line-row').forEach((row, idx) => {
        const numEl = row.querySelector('.line-number');
        if (numEl) numEl.textContent = idx + 1;
    });
}

// ── Add a new line ────────────────────────────────────────────────────────────
function addLine(container) {
    const prototype = container.dataset.prototype;
    if (!prototype) {
        console.warn('[invoice-lines] No data-prototype found on container.');
        return;
    }

    let index = parseInt(container.dataset.index ?? '0', 10);

    // Replace the placeholder with the current index.
    const newHtml = prototype.replace(/__name__/g, String(index));

    // Increment index for next insertion.
    container.dataset.index = String(index + 1);

    // Parse and append.
    const template = document.createElement('template');
    template.innerHTML = newHtml.trim();
    const newRow = template.content.firstElementChild;

    // Mark as a line row if not already.
    if (!newRow.classList.contains('invoice-line-row')) {
        newRow.classList.add('invoice-line-row');
    }

    // Animate entry.
    newRow.classList.add('animate__animated', 'animate__fadeInDown', 'animate__faster');
    newRow.addEventListener('animationend', () => {
        newRow.classList.remove('animate__animated', 'animate__fadeInDown', 'animate__faster');
    }, { once: true });

    // Update visible line number.
    const numEl = newRow.querySelector('.line-number');
    if (numEl) numEl.textContent = container.querySelectorAll('.invoice-line-row').length + 1;

    container.appendChild(newRow);
    attachRowListeners(newRow, container);
    updateTotals(container);

    // Focus the first editable field in the new row.
    const firstInput = newRow.querySelector('input:not([type="hidden"]):not([readonly]), select');
    if (firstInput) {
        firstInput.focus();
    }
}

// ── Bootstrap the module ──────────────────────────────────────────────────────
export default function initInvoiceLines() {
    // Support multiple collection containers on the same page (e.g. quote + expenses).
    document.querySelectorAll('[data-collection-add]').forEach((addBtn) => {
        const targetSelector = addBtn.dataset.target || '#invoice-lines-container';
        const container      = document.querySelector(targetSelector);

        if (!container) {
            console.warn(`[invoice-lines] Container "${targetSelector}" not found.`);
            return;
        }

        // Attach listeners to existing rows (edit form).
        container.querySelectorAll('.invoice-line-row').forEach((row) => {
            attachRowListeners(row, container);
        });

        // Initial totals pass.
        updateTotals(container);

        // "Add line" button.
        addBtn.addEventListener('click', () => addLine(container));
    });

    // ── Global remove button delegation ──────────────────────────────────────
    // Handles remove buttons added dynamically (belt-and-suspenders).
    document.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('[data-collection-remove]');
        if (!removeBtn) return;

        const row       = removeBtn.closest('.invoice-line-row');
        const container = row?.parentElement;
        if (!row || !container) return;

        // Prevent duplicate handling if already attached directly.
        if (removeBtn.dataset.listenerAttached) return;

        row.classList.add('animate__animated', 'animate__fadeOut', 'animate__faster');
        row.addEventListener('animationend', () => {
            row.remove();
            reindexRows(container);
            updateTotals(container);
        }, { once: true });
    });
}
