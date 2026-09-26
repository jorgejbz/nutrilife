/**
 * ==========================================================
 * PROGRESS SHOW
 * Gráficas de evolución de peso y composición corporal.
 * Lee los datos que la vista inyecta en `window.progressShowData`
 * — ver resources/views/progress/show.blade.php.
 * Los colores salen del tema activo (resources/js/chart-theme.js).
 * ==========================================================
 */

import { chartColors, onThemeChange } from '../chart-theme';

document.addEventListener('DOMContentLoaded', function () {

    const data = window.progressShowData;
    const weightCanvas = document.getElementById('weightChart');
    const compositionCanvas = document.getElementById('compositionChart');

    if (!data || typeof Chart === 'undefined') {
        return;
    }

    const { labels, weights, bodyFat, muscleMass } = data;
    let charts = [];

    function build() {
        charts.forEach((chart) => chart.destroy());
        charts = [];

        const c = chartColors();

        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { color: c.label },
                },
                tooltip: {
                    backgroundColor: c.tooltipBg,
                    borderColor: c.tooltipBorder,
                    borderWidth: 1,
                },
            },
            scales: {
                x: {
                    ticks: { color: c.label },
                    grid: { color: c.grid },
                },
                y: {
                    ticks: { color: c.label },
                    grid: { color: c.grid },
                },
            },
        };

        /* Peso */
        if (weightCanvas) {
            charts.push(new Chart(weightCanvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Peso (kg)',
                        data: weights,
                        borderColor: c.primary,
                        backgroundColor: c.primaryAlpha(.12),
                        fill: true,
                        tension: .35,
                    }],
                },
                options: chartOptions,
            }));
        }

        /* Composición corporal */
        if (compositionCanvas) {
            charts.push(new Chart(compositionCanvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Grasa (%)',
                            data: bodyFat,
                            borderColor: c.warning,
                            tension: .35,
                        },
                        {
                            label: 'Masa muscular (kg)',
                            data: muscleMass,
                            borderColor: c.info,
                            tension: .35,
                        },
                    ],
                },
                options: chartOptions,
            }));
        }
    }

    build();
    onThemeChange(build);
});
