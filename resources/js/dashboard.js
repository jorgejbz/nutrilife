/**
 * ==========================================================
 * NUTRILIFE — DASHBOARD CHARTS
 * Lee los datos inyectados por la vista en `window.dashboardData`
 * (ver resources/views/dashboard/index.blade.php, sección @section('js'))
 * y construye las 4 gráficas con Chart.js.
 *
 * Los colores salen del tema activo (resources/js/chart-theme.js) y
 * las gráficas se reconstruyen al cambiar entre claro y oscuro.
 * ==========================================================
 */

import { chartColors, onThemeChange } from './chart-theme';

document.addEventListener('DOMContentLoaded', function () {

    const data = window.dashboardData;

    if (!data || typeof Chart === 'undefined') {
        return;
    }

    let charts = [];

    function build() {
        charts.forEach((chart) => chart.destroy());
        charts = [];

        const c = chartColors();

        Chart.defaults.color = c.label;
        Chart.defaults.font.family = c.font;

        const tooltipBase = {
            backgroundColor: c.tooltipBg,
            borderColor: c.tooltipBorder,
            borderWidth: 1,
            padding: 9,
        };

        const hiddenAxisLine = { display: false };

        /* -----------------------------------------------------
           1) PACIENTES — CRECIMIENTO (línea)
        ----------------------------------------------------- */
        const patientsCanvas = document.getElementById('patientsGrowthChart');

        if (patientsCanvas && data.patients) {
            const ctx = patientsCanvas.getContext('2d');

            const gradient = ctx.createLinearGradient(0, 0, 0, 280);
            gradient.addColorStop(0, c.primaryAlpha(.28));
            gradient.addColorStop(1, c.primaryAlpha(0));

            charts.push(new Chart(patientsCanvas, {
                type: 'line',
                data: {
                    labels: data.patients.labels,
                    datasets: [{
                        data: data.patients.data,
                        borderColor: c.primary,
                        backgroundColor: gradient,
                        fill: true,
                        borderWidth: 2,
                        tension: .42,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: c.primary,
                        pointHoverBorderColor: c.pointBorder,
                        pointHoverBorderWidth: 3,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: { display: false },
                        tooltip: { ...tooltipBase, padding: 10, displayColors: false },
                    },
                    scales: {
                        x: {
                            border: hiddenAxisLine,
                            grid: { display: false },
                            ticks: { color: c.label, font: { size: 9 } },
                        },
                        y: {
                            beginAtZero: true,
                            border: hiddenAxisLine,
                            grid: { color: c.grid },
                            ticks: { color: c.label, precision: 0, font: { size: 8 } },
                        },
                    },
                },
            }));
        }

        /* -----------------------------------------------------
           2) PACIENTES — ACTIVOS / INACTIVOS (dona)
        ----------------------------------------------------- */
        const statusCanvas = document.getElementById('patientStatusChart');

        if (statusCanvas && data.patientStatus) {
            charts.push(new Chart(statusCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Activos', 'Inactivos'],
                    datasets: [{
                        data: [data.patientStatus.active, data.patientStatus.inactive],
                        backgroundColor: [c.primary, c.secondary],
                        borderColor: [c.grid, c.grid],
                        borderWidth: 1,
                        hoverOffset: 4,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '77%',
                    plugins: {
                        legend: { display: false },
                        tooltip: tooltipBase,
                    },
                },
            }));
        }

        /* -----------------------------------------------------
           3) CITAS DE LA SEMANA (barras)
        ----------------------------------------------------- */
        const appointmentsCanvas = document.getElementById('appointmentsChart');

        if (appointmentsCanvas && data.appointments) {
            charts.push(new Chart(appointmentsCanvas, {
                type: 'bar',
                data: {
                    labels: data.appointments.labels,
                    datasets: [{
                        data: data.appointments.data,
                        backgroundColor: c.primaryAlpha(.55),
                        hoverBackgroundColor: c.primary,
                        borderRadius: 8,
                        borderSkipped: false,
                        barThickness: 17,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { ...tooltipBase, displayColors: false },
                    },
                    scales: {
                        x: {
                            border: hiddenAxisLine,
                            grid: { display: false },
                            ticks: { color: c.label, font: { size: 8 } },
                        },
                        y: {
                            beginAtZero: true,
                            border: hiddenAxisLine,
                            grid: { color: c.grid },
                            ticks: { precision: 0, color: c.label, font: { size: 8 } },
                        },
                    },
                },
            }));
        }

        /* -----------------------------------------------------
           4) INGRESOS MENSUALES (línea de área)
        ----------------------------------------------------- */
        const incomeCanvas = document.getElementById('incomeChart');

        if (incomeCanvas && data.income) {
            const ctx = incomeCanvas.getContext('2d');

            const incomeGradient = ctx.createLinearGradient(0, 0, 0, 250);
            incomeGradient.addColorStop(0, c.primaryAlpha(.22));
            incomeGradient.addColorStop(1, c.primaryAlpha(0));

            charts.push(new Chart(incomeCanvas, {
                type: 'line',
                data: {
                    labels: data.income.labels,
                    datasets: [{
                        data: data.income.data,
                        borderColor: c.primary,
                        backgroundColor: incomeGradient,
                        fill: true,
                        borderWidth: 2,
                        tension: .42,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            ...tooltipBase,
                            displayColors: false,
                            callbacks: {
                                label: (context) => '$' + Number(context.raw).toLocaleString('es-MX'),
                            },
                        },
                    },
                    scales: {
                        x: {
                            border: hiddenAxisLine,
                            grid: { display: false },
                            ticks: { color: c.label, font: { size: 8 } },
                        },
                        y: {
                            beginAtZero: true,
                            border: hiddenAxisLine,
                            grid: { color: c.grid },
                            ticks: {
                                color: c.label,
                                font: { size: 8 },
                                callback: (value) => '$' + value,
                            },
                        },
                    },
                },
            }));
        }
    }

    build();
    onThemeChange(build);
});
