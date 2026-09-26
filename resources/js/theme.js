/**
 * ==========================================================
 * NUTRILIFE — CAMBIO DE TEMA CLARO / OSCURO
 *
 * - Guarda la preferencia en localStorage ("nutrilife-theme").
 * - Pone data-theme / data-bs-theme en <html>.
 * - Activa la hoja del modo elegido (*-light.css o *-dark.css)
 *   y deshabilita la otra (ver partials/theme-styles.blade.php).
 * - Emite el evento "nutrilife:themechange" para que otros
 *   módulos (p. ej. las gráficas) se actualicen.
 *
 * El botón puede repetirse varias veces en la misma página
 * (ej. versión desktop + versión móvil): todos se sincronizan.
 * ==========================================================
 */

(function () {

    const STORAGE_KEY = 'nutrilife-theme';

    function storeTheme(theme) {
        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch (e) {
            /* localStorage no disponible (modo privado, etc.) — no pasa nada */
        }
    }

    function currentTheme() {
        return document.documentElement.getAttribute('data-theme') === 'light'
            ? 'light'
            : 'dark';
    }

    function syncStylesheets(theme) {
        if (typeof window.nutrilifeSyncThemeStylesheets === 'function') {
            window.nutrilifeSyncThemeStylesheets(theme);
            return;
        }

        document.querySelectorAll('link[data-theme-stylesheet]').forEach(function (link) {
            link.disabled = link.getAttribute('data-theme-stylesheet') !== theme;
        });
    }

    function syncButtons(theme) {
        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            const icon = button.querySelector('[data-theme-icon]');
            const label = button.querySelector('[data-theme-label]');

            if (icon) {
                icon.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
            }

            if (label) {
                label.textContent = theme === 'light' ? 'Oscuro' : 'Claro';
            }

            button.setAttribute(
                'aria-label',
                theme === 'light' ? 'Cambiar a tema oscuro' : 'Cambiar a tema claro'
            );
        });
    }

    function applyTheme(theme) {
        const root = document.documentElement;

        root.setAttribute('data-theme', theme);

        /* AdminLTE 4 / Bootstrap 5.3 leen data-bs-theme. */
        root.setAttribute('data-bs-theme', theme);

        /* AdminLTE puede fijar data-bs-theme en el <aside class="app-sidebar">,
           lo cual pisa el de <html>; se sincroniza también ahí. */
        const sidebar = document.querySelector('.app-sidebar');
        if (sidebar) {
            sidebar.setAttribute('data-bs-theme', theme);
        }

        syncStylesheets(theme);
        syncButtons(theme);

        document.dispatchEvent(new CustomEvent('nutrilife:themechange', { detail: { theme: theme } }));
    }

    function toggleTheme() {
        const next = currentTheme() === 'light' ? 'dark' : 'light';
        applyTheme(next);
        storeTheme(next);
    }

    window.NutriLifeTheme = {
        current: currentTheme,
        apply: applyTheme,
        toggle: toggleTheme,
    };

    function init() {
        applyTheme(currentTheme());

        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            button.addEventListener('click', toggleTheme);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
