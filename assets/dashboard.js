/**
 * dashboard.js
 *
 * Initialises all Chart.js charts on the dashboard page.
 * This module is loaded lazily by app.js only when #chartCA is present.
 *
 * Expected DOM markup:
 *
 *   <!-- Chiffre d'affaires bar chart -->
 *   <canvas id="chartCA"
 *           data-labels='["Jan","Fév","Mar","Avr","Mai","Jun","Jul","Aoû","Sep","Oct","Nov","Déc"]'
 *           data-values='[12000,15000,9000,18000,22000,17000,25000,20000,30000,28000,35000,40000]'>
 *   </canvas>
 *
 *   <!-- Statut des projets donut chart -->
 *   <canvas id="chartProjets"
 *           data-labels='["En cours","Terminés","En attente","Annulés"]'
 *           data-values='[8,14,3,1]'
 *           data-colors='["#3B82F6","#10B981","#F59E0B","#EF4444"]'>
 *   </canvas>
 */

import {
    Chart,
    BarController,
    BarElement,
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    DoughnutController,
    ArcElement,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';

// Register only what we need to keep the bundle lean.
Chart.register(
    BarController,
    BarElement,
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    DoughnutController,
    ArcElement,
    Tooltip,
    Legend,
    Filler,
);

// ── Shared defaults ───────────────────────────────────────────────────────────
Chart.defaults.responsive          = true;
Chart.defaults.maintainAspectRatio = false;
Chart.defaults.font.family         = "'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif";
Chart.defaults.font.size           = 12;
Chart.defaults.color               = '#6B7280';
Chart.defaults.plugins.legend.labels.boxWidth  = 12;
Chart.defaults.plugins.legend.labels.padding   = 16;
Chart.defaults.plugins.legend.labels.font      = { size: 12, weight: '500' };
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(17, 24, 39, 0.85)';
Chart.defaults.plugins.tooltip.padding         = 10;
Chart.defaults.plugins.tooltip.cornerRadius    = 8;
Chart.defaults.plugins.tooltip.titleFont       = { size: 12, weight: '700' };
Chart.defaults.plugins.tooltip.bodyFont        = { size: 12 };
Chart.defaults.plugins.tooltip.displayColors   = true;
Chart.defaults.plugins.tooltip.boxPadding      = 4;

// ── Helper — parse JSON data attributes safely ────────────────────────────────
function parseAttr(canvas, attr, fallback = []) {
    try {
        const raw = canvas.dataset[attr];
        return raw ? JSON.parse(raw) : fallback;
    } catch (err) {
        console.warn(`[dashboard] Could not parse data-${attr}:`, err);
        return fallback;
    }
}

// ── Gradient factory ──────────────────────────────────────────────────────────
function createBlueGradient(ctx, chartArea) {
    const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
    gradient.addColorStop(0,   'rgba(59, 130, 246, 0.85)');
    gradient.addColorStop(0.5, 'rgba(59, 130, 246, 0.60)');
    gradient.addColorStop(1,   'rgba(59, 130, 246, 0.25)');
    return gradient;
}

// ── Chart 1: Chiffre d'affaires (bar) ─────────────────────────────────────────
function initChartCA() {
    const canvas = document.getElementById('chartCA');
    if (!canvas) return;

    const labels = parseAttr(canvas, 'labels');
    const values = parseAttr(canvas, 'values');

    // Use a deferred gradient so we have access to chartArea.
    let cachedGradient = null;

    const chart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label:            'Chiffre d\'affaires (€)',
                    data:             values,
                    backgroundColor:  (context) => {
                        const { chart: c } = context;
                        if (!c.chartArea) return 'rgba(59, 130, 246, 0.7)';
                        if (!cachedGradient) {
                            cachedGradient = createBlueGradient(c.ctx, c.chartArea);
                        }
                        return cachedGradient;
                    },
                    borderColor:      '#3B82F6',
                    borderWidth:      0,
                    borderRadius:     6,
                    borderSkipped:    false,
                    hoverBackgroundColor: '#2563EB',
                },
            ],
        },
        options: {
            animation: {
                duration: 600,
                easing:   'easeInOutQuart',
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        font: { size: 11 },
                    },
                },
                y: {
                    beginAtZero:  true,
                    grid: {
                        color:     'rgba(0, 0, 0, 0.04)',
                        lineWidth: 1,
                    },
                    border: {
                        dash: [4, 4],
                    },
                    ticks: {
                        font:     { size: 11 },
                        callback: (value) =>
                            new Intl.NumberFormat('fr-FR', {
                                style:                 'currency',
                                currency:              'EUR',
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0,
                            }).format(value),
                    },
                },
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const formatted = new Intl.NumberFormat('fr-FR', {
                                style:                 'currency',
                                currency:              'EUR',
                                minimumFractionDigits: 2,
                            }).format(context.parsed.y);
                            return `  ${formatted}`;
                        },
                    },
                },
            },
        },
    });

    return chart;
}

// ── Chart 2: Statut des projets (donut) ───────────────────────────────────────
function initChartProjets() {
    const canvas = document.getElementById('chartProjets');
    if (!canvas) return;

    const labels = parseAttr(canvas, 'labels');
    const values = parseAttr(canvas, 'values');
    const colors = parseAttr(canvas, 'colors', [
        '#3B82F6', // blue  — en cours
        '#10B981', // green — terminés
        '#F59E0B', // amber — en attente
        '#EF4444', // red   — annulés
        '#8B5CF6', // violet — autre
    ]);

    // Lighter border rings for the "gap" effect.
    const hoverColors = colors.map((c) => c + 'CC'); // ~80 % opacity

    const chart = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [
                {
                    label:                  'Projets',
                    data:                   values,
                    backgroundColor:        colors,
                    hoverBackgroundColor:    hoverColors,
                    borderColor:            '#ffffff',
                    borderWidth:            3,
                    hoverBorderWidth:       3,
                    hoverOffset:            6,
                },
            ],
        },
        options: {
            cutout: '68%',
            animation: {
                animateRotate: true,
                animateScale:  false,
                duration:      700,
                easing:        'easeInOutQuart',
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        generateLabels: (chart) => {
                            const { data } = chart;
                            const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                            return data.labels.map((label, i) => ({
                                text:        `${label} — ${data.datasets[0].data[i]} (${Math.round((data.datasets[0].data[i] / total) * 100)} %)`,
                                fillStyle:   data.datasets[0].backgroundColor[i],
                                strokeStyle: data.datasets[0].backgroundColor[i],
                                lineWidth:   0,
                                hidden:      false,
                                index:       i,
                            }));
                        },
                    },
                },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const pct   = ((context.parsed / total) * 100).toFixed(1);
                            return `  ${context.parsed} projet${context.parsed > 1 ? 's' : ''} (${pct} %)`;
                        },
                    },
                },
            },
        },
        // Draw a centred total count inside the donut.
        plugins: [
            {
                id: 'donutCenterText',
                afterDraw(chart) {
                    const { ctx, data, chartArea } = chart;
                    const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                    const cx    = (chartArea.left + chartArea.right) / 2;
                    const cy    = (chartArea.top  + chartArea.bottom) / 2;

                    ctx.save();
                    ctx.textAlign    = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.font         = `700 ${Math.min(chartArea.width, chartArea.height) * 0.16}px Inter, sans-serif`;
                    ctx.fillStyle    = '#1E3A5F';
                    ctx.fillText(total, cx, cy - 8);

                    ctx.font      = `500 ${Math.min(chartArea.width, chartArea.height) * 0.09}px Inter, sans-serif`;
                    ctx.fillStyle = '#6B7280';
                    ctx.fillText('projets', cx, cy + 14);
                    ctx.restore();
                },
            },
        ],
    });

    return chart;
}

// ── Optional: sparkline mini-charts on KPI cards ──────────────────────────────
function initSparklines() {
    document.querySelectorAll('canvas.sparkline').forEach((canvas) => {
        const values = parseAttr(canvas, 'values');
        const color  = canvas.dataset.color || '#3B82F6';

        new Chart(canvas, {
            type: 'line',
            data: {
                labels:   values.map((_, i) => i),
                datasets: [
                    {
                        data:            values,
                        borderColor:     color,
                        borderWidth:     2,
                        pointRadius:     0,
                        fill:            true,
                        backgroundColor: color + '22',
                        tension:         0.4,
                    },
                ],
            },
            options: {
                animation:            { duration: 400 },
                plugins:              { legend: { display: false }, tooltip: { enabled: false } },
                scales:               { x: { display: false }, y: { display: false } },
                events:               [],
            },
        });
    });
}

// ── Public API ────────────────────────────────────────────────────────────────
export default function initDashboard() {
    initChartCA();
    initChartProjets();
    initSparklines();
}
