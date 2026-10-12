/**
 * ==========================================================
 * HISTORIAL CLÍNICO DEL PACIENTE (crear y editar)
 * resources/views/patients/partials/clinical-fields.blade.php
 *
 * Al marcar una patología o alergia se muestran y habilitan sus
 * detalles (fecha/notas o severidad). Al desmarcarla se ocultan y se
 * deshabilitan para que no se envíen. Actualiza el contador de cada
 * bloque. Sin JS, el formulario sigue funcionando: el servidor solo
 * guarda lo que viene marcado.
 * ==========================================================
 */

document.addEventListener('DOMContentLoaded', function () {

    const section = document.querySelector('[data-clinical]');

    if (!section) {
        return;
    }

    function updateCount(group) {
        const counter = section.querySelector('[data-clinical-count="' + group + '"]');
        if (!counter) {
            return;
        }
        const total = section.querySelectorAll('[data-clinical-toggle="' + group + '"]:checked').length;
        counter.textContent = total === 1 ? '1 seleccionada' : total + ' seleccionadas';
        counter.classList.toggle('has-items', total > 0);
    }

    function sync(checkbox) {
        const option = checkbox.closest('[data-clinical-option]');
        const details = option.querySelector('.clinical-details');

        option.classList.toggle('is-selected', checkbox.checked);

        if (details) {
            details.hidden = !checkbox.checked;
            details.querySelectorAll('input, select, textarea').forEach(function (field) {
                field.disabled = !checkbox.checked;
            });
        }

        updateCount(checkbox.dataset.clinicalToggle);
    }

    section.querySelectorAll('[data-clinical-toggle]').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            sync(checkbox);

            if (checkbox.checked) {
                checkbox.closest('[data-clinical-option]')
                    .querySelector('.clinical-details input:not([type="radio"])')
                    ?.focus({ preventScroll: true });
            }
        });

        sync(checkbox);
    });
});
