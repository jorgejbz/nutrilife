/**
 * ==========================================================
 * NUTRILIFE — CATÁLOGO DE PLATILLOS (resources/views/dishes/index)
 *
 * Mismo comportamiento que el Catálogo de alimentos:
 *  - Los selects de filtro envían el formulario al cambiar.
 *  - Estado de carga mientras se recarga con nuevos filtros o página.
 *  - En celular, los filtros se pliegan detrás del botón "Filtros".
 *  - Diálogo de detalle: copia el <template> del platillo
 *    (ingredientes y aporte ya calculados en el servidor).
 * ==========================================================
 */

(function () {

    const form = document.getElementById('dishFilters');
    const results = document.getElementById('dishResults');
    const loading = results?.querySelector('.food-loading');

    const showLoading = () => {
        if (!results || !loading) {
            return;
        }
        results.setAttribute('aria-busy', 'true');
        results.classList.add('is-loading');
        loading.hidden = false;
    };

    // Al volver con "Atrás" se quita el estado de carga restaurado
    window.addEventListener('pageshow', () => {
        results?.removeAttribute('aria-busy');
        results?.classList.remove('is-loading');
        if (loading) {
            loading.hidden = true;
        }
    });

    /* ------------------------------------------------------
     | Filtros
     * ------------------------------------------------------ */
    if (form) {
        form.querySelectorAll('select[data-auto-submit]').forEach((select) => {
            select.addEventListener('change', () => form.requestSubmit());
        });

        form.addEventListener('submit', () => {
            // Sin "page" (vuelve a la 1) y sin campos vacíos o por defecto: URL limpia
            const skipped = Array.from(form.elements).filter((element) => element.name && (
                element.value.trim() === '' || (element.name === 'orden' && element.value === 'nombre')
            ));
            skipped.forEach((element) => { element.disabled = true; });
            setTimeout(() => skipped.forEach((element) => { element.disabled = false; }), 0);
            showLoading();
        });

        const toggle = form.querySelector('.food-filters-toggle');
        const panel = document.getElementById('dishFilterPanel');
        const narrow = window.matchMedia('(max-width: 575.98px)');

        const syncPanel = () => {
            if (!toggle || !panel) {
                return;
            }

            if (narrow.matches) {
                toggle.hidden = false;
                panel.hidden = toggle.getAttribute('aria-expanded') !== 'true';
            } else {
                toggle.hidden = true;
                panel.hidden = false;
            }
        };

        toggle?.addEventListener('click', () => {
            const open = toggle.getAttribute('aria-expanded') !== 'true';
            toggle.setAttribute('aria-expanded', String(open));
            panel.hidden = !open;
            if (open) {
                panel.querySelector('select')?.focus();
            }
        });

        narrow.addEventListener('change', syncPanel);
        syncPanel();
    }

    document.querySelectorAll('[data-dish-nav]').forEach((link) => {
        link.addEventListener('click', (event) => {
            if (!event.metaKey && !event.ctrlKey && !event.shiftKey && event.button === 0) {
                showLoading();
            }
        });
    });

    /* ------------------------------------------------------
     | Detalle del platillo
     * ------------------------------------------------------ */
    const dialog = document.getElementById('dishDetailDialog');
    const body = dialog?.querySelector('[data-dish-detail-body]');

    if (!dialog || !body) {
        return;
    }

    let opener = null;

    const close = () => {
        if (typeof dialog.close === 'function') {
            dialog.close();
        } else {
            dialog.removeAttribute('open');
            dialog.dispatchEvent(new Event('close'));
        }
    };

    const open = (button) => {
        const template = document.getElementById(`dish-detail-${button.dataset.dishDetail}`);
        if (!template) {
            return;
        }

        opener = button;
        body.replaceChildren(template.content.cloneNode(true));
        body.querySelectorAll('[data-close-dialog]').forEach((button) => button.addEventListener('click', close));
        document.documentElement.classList.add('nl-dialog-open');

        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        } else {
            dialog.setAttribute('open', '');
        }
    };

    document.querySelectorAll('[data-dish-detail]').forEach((button) => {
        button.addEventListener('click', () => open(button));
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            close();
        }
    });

    dialog.addEventListener('close', () => {
        document.documentElement.classList.remove('nl-dialog-open');
        opener?.focus();
    });

})();
