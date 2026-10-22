/**
 * ==========================================================
 * PAYMENTS CREATE
 * Relación payments.appointment_id → appointments.patient_id:
 * la lista de "Cita relacionada" solo muestra citas del paciente
 * elegido. El backend (PaymentController@store) vuelve a validarlo.
 *
 * Vista previa de la referencia NutriLife: SOLO informativa. La
 * referencia definitiva la genera el servidor al guardar
 * (PaymentReferenceService); este formulario no la envía.
 * ==========================================================
 */

import { bindPatientAppointmentFilter } from '../patient-appointment-filter';

document.addEventListener('DOMContentLoaded', function () {
    bindPatientAppointmentFilter(
        document.getElementById('patient_id'),
        document.getElementById('appointment_id')
    );

    const method = document.getElementById('payment_method');
    const preview = document.getElementById('paymentRefPreview');

    if (!method || !preview) {
        return;
    }

    const prefixOf = () => method.selectedOptions[0]?.dataset.prefix || 'OTR';

    // Alta: muestra el prefijo que tendrá según el método elegido.
    const code = document.getElementById('paymentRefPreviewCode');

    if (code) {
        method.addEventListener('change', function () {
            code.textContent = code.textContent.replace(/^[A-Z]{3}/, prefixOf());
        });
        return;
    }

    // Edición: avisa si el cambio de método generará una referencia nueva.
    const note = document.getElementById('paymentRefPreviewNote');

    if (!note || preview.dataset.pending !== '1') {
        return;
    }

    const original = note.textContent.trim();

    method.addEventListener('change', function () {
        const changes = prefixOf() !== preview.dataset.currentPrefix;

        note.textContent = changes
            ? 'Al guardar, este pago recibirá una referencia nueva con prefijo ' + prefixOf() + '. La anterior queda en el historial.'
            : original;
        note.classList.toggle('is-warning', changes);
    });
});
