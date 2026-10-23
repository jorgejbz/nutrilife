/**
 * ==========================================================
 * NUTRILIFE — COLORES DE GRÁFICAS SEGÚN EL TEMA
 *
 * Las gráficas (Chart.js) ya no llevan colores fijos: los leen de
 * las variables --chart-* definidas en public/css/themes/admin-light.css y
 * admin-dark.css. Al cambiar de modo, se vuelven a pintar.
 *
 * Uso:
 *   import { chartColors, onThemeChange } from '../chart-theme';
 *   const c = chartColors();
 *   onThemeChange(() => { ...actualizar y chart.update()... });
 * ==========================================================
 */

function cssVar(name, fallback) {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    return value || fallback;
}

/** Devuelve "rgba(r, g, b, a)" a partir de una variable --*-rgb. */
function rgbaVar(name, alpha, fallback) {
    const rgb = cssVar(name, fallback);
    return `rgba(${rgb}, ${alpha})`;
}

export function chartColors() {
    return {
        grid: cssVar('--chart-grid', 'rgba(255,255,255,.06)'),
        label: cssVar('--chart-label', 'rgba(225,238,220,.55)'),
        primary: cssVar('--chart-primary', '#97D7A5'),
        primaryAlpha: (alpha) => rgbaVar('--chart-primary-rgb', alpha, '151, 215, 165'),
        secondary: cssVar('--chart-secondary', '#5E7D66'),
        tooltipBg: cssVar('--chart-tooltip-bg', 'rgba(18,38,26,.96)'),
        tooltipBorder: cssVar('--chart-tooltip-border', 'rgba(151,215,165,.18)'),
        pointBorder: cssVar('--chart-point-border', '#1B3625'),
        warning: cssVar('--chart-warning', '#D9BF70'),
        info: cssVar('--chart-info', '#87BEB0'),
        // Series categóricas en orden fijo (ver admin-light/admin-dark.css)
        cat: [1, 2, 3, 4].map((slot, i) => cssVar(`--chart-cat-${slot}`, ['#4F9F58', '#3987E5', '#C98500', '#D55181'][i])),
        surface: cssVar('--surface-solid', '#1F3D2A'),
        font: cssVar('--nl-font-family', 'Poppins, sans-serif'),
    };
}

/**
 * Ejecuta callback cada vez que el usuario cambia de tema.
 * Se espera un frame para que la hoja del nuevo modo ya esté aplicada.
 */
export function onThemeChange(callback) {
    document.addEventListener('nutrilife:themechange', function () {
        requestAnimationFrame(function () {
            requestAnimationFrame(callback);
        });
    });
}
