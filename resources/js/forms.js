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
 * data-confirm="…" en un <form> pide confirmación antes de enviarlo
 * con el diálogo de NutriLife (ver "Diálogo de confirmación" abajo).
 * ==========================================================
 */

(function () {

    /* ------------------------------------------------------
     | Diálogo de confirmación
     |
     | Sustituye a window.confirm(). Atributos del <form>:
     |   data-confirm="…"            mensaje (obligatorio; también es
     |                               el texto si no hay <dialog>)
     |   data-confirm-title="…"      título
     |   data-confirm-accept="…"     texto del botón de confirmar
     |   data-confirm-cancel="…"     texto del botón de cancelar
     |   data-confirm-variant="…"    danger | plan | (vacío = neutro)
     |   data-confirm-icon="fa-…"    ícono de Font Awesome
     |   data-confirm-details="#id"  <template> con contenido extra
     |
     | El foco empieza en "Cancelar" y Escape o un clic fuera del
     | cuadro cancelan: así no se confirma nada por accidente.
     * ------------------------------------------------------ */
    const supportsDialog = typeof HTMLDialogElement === 'function';

    function confirmDefaults(form) {
        const method = (form.querySelector('input[name="_method"]')?.value || form.getAttribute('method') || '').toUpperCase();

        if (method === 'DELETE') {
            return { variant: 'danger', icon: 'fa-trash-alt', title: 'Confirmar eliminación', accept: 'Sí, eliminar' };
        }

        return { variant: '', icon: 'fa-question', title: '¿Confirmas esta acción?', accept: 'Confirmar' };
    }

    function buildDialog(form) {
        const defaults = confirmDefaults(form);
        const data = form.dataset;
        const variant = data.confirmVariant ?? defaults.variant;
        const uid = 'nl-confirm-' + Date.now();

        const dialog = document.createElement('dialog');
        dialog.className = 'nl-confirm' + (variant ? ' nl-confirm--' + variant : '');
        dialog.setAttribute('aria-labelledby', uid + '-title');
        dialog.setAttribute('aria-describedby', uid + '-message');

        dialog.innerHTML = `
            <form method="dialog" class="nl-confirm__box">
                <div class="nl-confirm__head">
                    <span class="nl-confirm__icon" aria-hidden="true"><i class="fas"></i></span>
                    <h2 class="nl-confirm__title" id="${uid}-title"></h2>
                    <p class="nl-confirm__message" id="${uid}-message"></p>
                </div>
                <div class="nl-confirm__details" hidden></div>
                <div class="nl-confirm__actions">
                    <button type="submit" value="cancel" class="nl-confirm__btn nl-confirm__btn--ghost" data-confirm-cancel-button></button>
                    <button type="submit" value="ok" class="nl-confirm__btn nl-confirm__btn--accept"></button>
                </div>
            </form>`;

        dialog.querySelector('.nl-confirm__icon i').classList.add(data.confirmIcon || defaults.icon);
        dialog.querySelector('.nl-confirm__title').textContent = data.confirmTitle || defaults.title;
        dialog.querySelector('.nl-confirm__message').textContent = data.confirm;
        dialog.querySelector('[value="cancel"]').textContent = data.confirmCancel || 'Cancelar';
        dialog.querySelector('[value="ok"]').textContent = data.confirmAccept || defaults.accept;

        const template = data.confirmDetails ? document.querySelector(data.confirmDetails) : null;

        if (template instanceof HTMLTemplateElement) {
            const details = dialog.querySelector('.nl-confirm__details');
            details.appendChild(template.content.cloneNode(true));
            details.hidden = false;
        }

        return dialog;
    }

    function askConfirmation(form) {
        if (!supportsDialog) {
            return Promise.resolve(window.confirm(form.dataset.confirm));
        }

        return new Promise(function (resolve) {
            const dialog = buildDialog(form);
            const opener = document.activeElement;

            document.body.appendChild(dialog);
            dialog.showModal();
            /* El foco va a Cancelar sin desplazar el diálogo: en pantallas
               bajas el título debe ser lo primero que se ve. */
            dialog.querySelector('[data-confirm-cancel-button]').focus({ preventScroll: true });
            dialog.scrollTop = 0;

            /* Clic en el fondo oscuro (fuera del cuadro) = cancelar */
            dialog.addEventListener('click', function (event) {
                if (event.target === dialog) {
                    dialog.close('cancel');
                }
            });

            dialog.addEventListener('close', function () {
                const accepted = dialog.returnValue === 'ok';

                dialog.remove();

                if (!accepted && opener instanceof HTMLElement) {
                    opener.focus();
                }

                resolve(accepted);
            }, { once: true });
        });
    }

    document.addEventListener('submit', function (event) {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || event.defaultPrevented) {
            return;
        }

        /* Confirmación antes de acciones destructivas o importantes:
           <form data-confirm="¿Eliminar…?">. Si se cancela, no se envía.
           Al aceptar se vuelve a enviar el formulario, ya marcado como
           confirmado, para que siga el flujo normal (y la protección
           contra doble envío de abajo). */
        if (form.dataset.confirm && form.dataset.confirmed !== '1') {
            event.preventDefault();

            if (form.dataset.confirmOpen === '1') {
                return;
            }

            form.dataset.confirmOpen = '1';
            const submitter = event.submitter;

            askConfirmation(form).then(function (accepted) {
                delete form.dataset.confirmOpen;

                if (!accepted) {
                    return;
                }

                form.dataset.confirmed = '1';

                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit(submitter && submitter.form === form ? submitter : undefined);
                } else {
                    form.submit();
                }

                delete form.dataset.confirmed;
            });

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
            || input.dataset.nlDate // usa el calendario de NutriLife (date-picker.js)
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
