{{-- =========================================================
     THEME INIT (anti-parpadeo)
     Se incluye automáticamente desde partials/theme-styles, antes
     de los <link> de CSS. Es síncrono: lee localStorage y pone
     data-theme en <html> antes de que el navegador pinte, para
     que nunca se vea un destello del tema equivocado.

     Prioridad: preferencia guardada > sistema operativo > oscuro.
     La clave anterior ("nutriadmin-theme") se migra sola a
     "nutrilife-theme" para no perder la elección del usuario.
     ========================================================= --}}
<script>
    (function () {
        var KEY = 'nutrilife-theme';
        var LEGACY_KEY = 'nutriadmin-theme';
        var theme = null;

        try {
            theme = localStorage.getItem(KEY);

            if (!theme && localStorage.getItem(LEGACY_KEY)) {
                theme = localStorage.getItem(LEGACY_KEY);
                localStorage.setItem(KEY, theme);
                localStorage.removeItem(LEGACY_KEY);
            }
        } catch (e) { /* localStorage no disponible */ }

        if (theme !== 'light' && theme !== 'dark') {
            theme = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches
                ? 'light'
                : 'dark';
        }

        var root = document.documentElement;
        root.setAttribute('data-theme', theme);
        root.setAttribute('data-bs-theme', theme);

        /* Activa solo la hoja del modo actual (*-light.css o *-dark.css).
           theme.js vuelve a llamarla al cambiar de modo. */
        window.nutrilifeSyncThemeStylesheets = function (current) {
            var links = document.querySelectorAll('link[data-theme-stylesheet]');
            for (var i = 0; i < links.length; i++) {
                links[i].disabled = links[i].getAttribute('data-theme-stylesheet') !== current;
            }
        };

        /* Red de seguridad: si cualquier script cambia data-theme, las hojas
           se sincronizan solas (no dependen de que theme.js esté compilado). */
        if (window.MutationObserver) {
            new MutationObserver(function () {
                window.nutrilifeSyncThemeStylesheets(root.getAttribute('data-theme') === 'light' ? 'light' : 'dark');
            }).observe(root, { attributes: true, attributeFilter: ['data-theme'] });
        }
    })();
</script>
