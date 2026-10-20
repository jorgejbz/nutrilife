/**
 * ==========================================================
 * NUTRILIFE — PROTECCIÓN DE ENVÍO DE FORMULARIOS
 *
 * Al enviar un formulario POST, deshabilita su botón de envío y
 * muestra "Guardando…" para evitar registros duplicados por doble
 * clic. Se respeta cualquier confirm() previo (si el usuario cancela,
 * no se bloquea nada) y la validación nativa del navegador (si hay
 * campos inválidos el evento submit ni siquiera se dispara).
 *
 * Un formulario puede excluirse con data-no-submit-guard y un botón
 * puede definir su texto con data-loading-text="…".
 *
 * data-confirm="…" en un <form> pide confirmación antes de enviarlo.
 * ==========================================================
 */

(function () {

    document.addEventListener('submit', function (event) {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || event.defaultPrevented) {
            return;
        }

        /* Confirmación antes de acciones destructivas o importantes:
           <form data-confirm="¿Eliminar…?">. Si se cancela, no se envía. */
        if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
            event.preventDefault();
            return;
        }

        if ((form.getAttribute('method') || 'get').toLowerCase() !== 'post' || form.hasAttribute('data-no-submit-guard')) {
            return;
        }

        const button = event.submitter || form.querySelector('[type="submit"]');

        if (!button || button.disabled) {
            return;
        }

        /* Se deshabilita en el siguiente ciclo para que el valor del botón
           (si tiene name) todavía viaje con el formulario. */
        setTimeout(function () {
            button.dataset.originalHtml = button.innerHTML;
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> '
                + (button.dataset.loadingText || 'Guardando…');
        }, 0);
    });

    /* Si el usuario regresa con el botón "atrás", se restablecen los botones. */
    window.addEventListener('pageshow', function () {
        document.querySelectorAll('button[aria-busy="true"]').forEach(function (button) {
            button.disabled = false;
            button.removeAttribute('aria-busy');
            if (button.dataset.originalHtml) {
                button.innerHTML = button.dataset.originalHtml;
            }
        });
    });


    /* ------------------------------------------------------
     | Campos de fecha y hora: abrir el calendario con un clic
     | en cualquier parte del campo (antes solo funcionaba el
     | icono, que además era invisible en modo claro).
     | Se puede seguir escribiendo la fecha con el teclado.
     * ------------------------------------------------------ */
    const PICKER_TYPES = ['date', 'datetime-local', 'time', 'month', 'week'];

    document.addEventListener('click', function (event) {
        const input = event.target;

        if (!(input instanceof HTMLInputElement)
            || !PICKER_TYPES.includes(input.type)
            || input.disabled
            || input.readOnly
            || typeof input.showPicker !== 'function') {
            return;
        }

        try {
            input.showPicker();
        } catch (error) {
            /* Algunos navegadores lo bloquean (p. ej. dentro de iframes): queda el comportamiento nativo. */
        }
    });

})();
