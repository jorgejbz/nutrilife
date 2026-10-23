/**
 * ==========================================================
 * NUTRILIFE — DASHBOARD CHARTS
 * Lee los datos reales que calcula App\Services\DashboardStats y la
 * vista deja en `window.dashboardData`:
 *   patients     { labels, total, new }  → pacientes acumulados por mes
 *   status       { active, inactive }    → dona activos / inactivos
 *   appointments { labels, data, today } → citas de la semana actual
 *   income       { labels, data, pending } → cobrado vs. pendiente por mes
 *   paymentMethods, appointmentStatus, consultations, demographics,
 *   pathologies, bmi, weight → sección «Análisis» (partials/insights)
 * Si una gráfica no tiene datos, la vista no dibuja su <canvas> y
 * muestra un mensaje en su lugar.
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

        // Ejes recesivos comunes; `horizontal` invierte categoría y valor
        const axes = ({ stacked = false, horizontal = false, money = false } = {}) => {
            const category = {
                stacked,
                border: hiddenAxisLine,
                grid: { display: false },
                ticks: { color: c.label, font: { size: 9 } },
            };
            const value = {
                stacked,
                beginAtZero: true,
                border: hiddenAxisLine,
                grid: { color: c.grid },
                ticks: {
                    color: c.label,
                    precision: 0,
                    font: { size: 8 },
                    ...(money ? { callback: (v) => money$(v) } : {}),
                },
            };

            return horizontal ? { x: value, y: category } : { x: category, y: value };
        };

        // Barras: extremos de 4 px y separación de 2 px con el color de la tarjeta
        const barStyle = (color) => ({
            backgroundColor: color,
            hoverBackgroundColor: color,
            borderColor: c.surface,
            borderWidth: 1,
            borderRadius: 4,
            borderSkipped: 'start',
            maxBarThickness: 22,
        });

        const plural = (n, one, many) => `${n} ${n === 1 ? one : many}`;

        /* -----------------------------------------------------
           1) PACIENTES — NUEVOS (barras) Y ACUMULADO (línea), un solo eje
        ----------------------------------------------------- */
        const patientsCanvas = document.getElementById('patientsGrowthChart');

        if (patientsCanvas && data.patients) {
            charts.push(new Chart(patientsCanvas, {
                data: {
                    labels: data.patients.labels,
                    datasets: [
                        {
                            type: 'line',
                            label: 'Total acumulado',
                            data: data.patients.total,
                            borderColor: c.cat[0],
                            backgroundColor: c.cat[0],
                            borderWidth: 2,
                            tension: .35,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBorderColor: c.pointBorder,
                            pointBorderWidth: 2,
                            order: 0,
                        },
                        {
                            type: 'bar',
                            label: 'Nuevos',
                            data: data.patients.new,
                            ...barStyle(c.cat[1]),
                            order: 1,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            ...tooltipBase,
                            padding: 10,
                            callbacks: {
                                label: (context) => context.dataset.type === 'line'
                                    ? `Total: ${plural(context.raw, 'paciente', 'pacientes')}`
                                    : `Nuevos: ${context.raw}`,
                            },
                        },
                    },
                    scales: axes(),
                },
            }));
        }

        /* -----------------------------------------------------
           2) PACIENTES — ACTIVOS / INACTIVOS (dona)
        ----------------------------------------------------- */
        const statusCanvas = document.getElementById('patientStatusChart');

        if (statusCanvas && data.status) {
            charts.push(new Chart(statusCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Activos', 'Inactivos'],
                    datasets: [{
                        data: [data.status.active, data.status.inactive],
                        // Inactivos en gris neutro (mismo criterio que /pacientes)
                        backgroundColor: [c.primary, c.label],
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
                        // La barra de hoy se resalta
                        backgroundColor: data.appointments.data.map((_, i) => (i === data.appointments.today ? c.primary : c.primaryAlpha(.45))),
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
                        tooltip: {
                            ...tooltipBase,
                            displayColors: false,
                            callbacks: { label: (context) => `${context.raw} ${context.raw === 1 ? 'cita' : 'citas'}` },
                        },
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
                            ticks: { precision: 0, color: c.label, font: { size: 8 } },
                        },
                    },
                },
            }));
        }

        /* -----------------------------------------------------
           4) INGRESOS COBRADOS VS. PENDIENTES (barras apiladas)
        ----------------------------------------------------- */
        const incomeCanvas = document.getElementById('incomeChart');

        if (incomeCanvas && data.income) {
            charts.push(new Chart(incomeCanvas, {
                type: 'bar',
                data: {
                    labels: data.income.labels,
                    datasets: [
                        { label: 'Cobrado', data: data.income.data, ...barStyle(c.cat[0]) },
                        { label: 'Pendiente', data: data.income.pending || [], ...barStyle(c.cat[1]) },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            ...tooltipBase,
                            callbacks: {
                                label: (context) => `${context.dataset.label}: ${money$(context.raw)}`,
                            },
                        },
                    },
                    scales: axes({ stacked: true, money: true }),
                },
            }));
        }

        /* -----------------------------------------------------
           5) ESTADO DE LAS CITAS POR MES (barras apiladas, 4 estados
              en orden fijo: cat-1..4 validados como vecinos)
        ----------------------------------------------------- */
        const statusByMonthCanvas = document.getElementById('appointmentStatusChart');

        if (statusByMonthCanvas && data.appointmentStatus) {
            charts.push(new Chart(statusByMonthCanvas, {
                type: 'bar',
                data: {
                    labels: data.appointmentStatus.labels,
                    datasets: Object.entries(data.appointmentStatus.series).map(([status, values], i) => ({
                        label: status,
                        data: values,
                        ...barStyle(c.cat[i]),
                    })),
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            ...tooltipBase,
                            callbacks: { label: (context) => `${context.dataset.label}: ${context.raw}` },
                        },
                    },
                    scales: axes({ stacked: true }),
                },
            }));
        }

        /* -----------------------------------------------------
           6) BARRAS DE UNA SOLA SERIE (método de pago, IMC,
              patologías, evolución de peso)
        ----------------------------------------------------- */
        const singleBars = (id, series, { horizontal = false, money = false, unit = ['paciente', 'pacientes'] } = {}) => {
            const canvas = document.getElementById(id);

            if (!canvas || !series) {
                return;
            }

            charts.push(new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: series.labels,
                    datasets: [{ data: series.data, ...barStyle(c.primary) }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: horizontal ? 'y' : 'x',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            ...tooltipBase,
                            displayColors: false,
                            callbacks: {
                                label: (context) => (money ? money$(context.raw) : plural(context.raw, unit[0], unit[1])),
                            },
                        },
                    },
                    scales: axes({ horizontal, money }),
                },
            }));
        };

        singleBars('paymentMethodsChart', data.paymentMethods, { horizontal: true, money: true });
        singleBars('bmiChart', data.bmi);
        singleBars('pathologiesChart', data.pathologies, { horizontal: true });
        singleBars('weightChart', data.weight);

        /* -----------------------------------------------------
           7) CONSULTAS POR MES (área, una serie)
        ----------------------------------------------------- */
        const consultationsCanvas = document.getElementById('consultationsChart');

        if (consultationsCanvas && data.consultations) {
            const gradient = consultationsCanvas.getContext('2d').createLinearGradient(0, 0, 0, 280);
            gradient.addColorStop(0, c.primaryAlpha(.24));
            gradient.addColorStop(1, c.primaryAlpha(0));

            charts.push(new Chart(consultationsCanvas, {
                type: 'line',
                data: {
                    labels: data.consultations.labels,
                    datasets: [{
                        data: data.consultations.data,
                        borderColor: c.primary,
                        backgroundColor: gradient,
                        fill: true,
                        borderWidth: 2,
                        tension: .35,
                        pointRadius: 4,
                        pointHoverRadius: 6,
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
                            ...tooltipBase,
                            displayColors: false,
                            callbacks: { label: (context) => plural(context.raw, 'consulta', 'consultas') },
                        },
                    },
                    scales: axes(),
                },
            }));
        }

        /* -----------------------------------------------------
           8) PACIENTES POR SEXO Y EDAD (barras horizontales agrupadas;
              3 series = cat-2..4, validadas entre todos los pares)
        ----------------------------------------------------- */
        const demographicsCanvas = document.getElementById('demographicsChart');

        if (demographicsCanvas && data.demographics) {
            charts.push(new Chart(demographicsCanvas, {
                type: 'bar',
                data: {
                    labels: data.demographics.labels.map((range) => `${range} años`),
                    datasets: Object.entries(data.demographics.series).map(([sex, values], i) => ({
                        label: sex,
                        data: values,
                        ...barStyle(c.cat[i + 1]),
                        maxBarThickness: 14,
                    })),
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    interaction: { intersect: false, mode: 'index', axis: 'y' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            ...tooltipBase,
                            callbacks: { label: (context) => `${context.dataset.label}: ${context.raw}` },
                        },
                    },
                    scales: axes({ horizontal: true }),
                },
            }));
        }
    }

    function money$(value) {
        return '$' + Number(value).toLocaleString('es-MX', { maximumFractionDigits: 0 });
    }

    build();
    onThemeChange(build);
});
