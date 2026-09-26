/**
 * ==========================================================
 * PATIENTS SHOW
 * Gráfica de evolución de peso (Chart.js). Lee los datos que
 * la vista inyecta en `window.patientShowData` — ver
 * resources/views/patients/show.blade.php, sección @section('js').
 * Los colores salen del tema activo (resources/js/chart-theme.js).
 * ==========================================================
 */

import { chartColors, onThemeChange } from '../chart-theme';

document.addEventListener('DOMContentLoaded', function () {

    const canvas = document.getElementById('weightChart');
    const data = window.patientShowData;

    if (!canvas || !data || typeof Chart === 'undefined') {
        return;
    }

    let chart = null;

    function build() {
        if (chart) {
            chart.destroy();
        }

        const c = chartColors();
        const ctx = canvas.getContext('2d');

        const gradient = ctx.createLinearGradient(0, 0, 0, 310);
        gradient.addColorStop(0, c.primaryAlpha(.25));
        gradient.addColorStop(1, c.primaryAlpha(0));

        chart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: data.weightLabels,
                datasets: [{
                    label: 'Peso',
                    data: data.weightData,
                    borderColor: c.primary,
                    backgroundColor: gradient,
                    borderWidth: 2,
                    fill: true,
                    tension: .42,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: c.primary,
                    pointBorderColor: c.pointBorder,
                    pointBorderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: c.tooltipBg,
                        borderColor: c.tooltipBorder,
                        borderWidth: 1,
                        padding: 10,
                        displayColors: false,
                        callbacks: {
                            label: (context) => 'Peso: ' + context.parsed.y + ' kg',
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        border: { display: false },
                        grid: { color: c.grid },
                        ticks: {
                            color: c.label,
                            font: { size: 8 },
                            callback: (value) => value + ' kg',
                        },
                    },
                    x: {
                        border: { display: false },
                        grid: { display: false },
                        ticks: {
                            color: c.label,
                            font: { size: 8 },
                        },
                    },
                },
            },
        });
    }

    build();
    onThemeChange(build);
});
