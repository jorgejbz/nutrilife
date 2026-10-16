/**
 * ==========================================================
 * CONSULTATIONS CREATE
 * - Contadores de caracteres para observaciones y recomendaciones.
 * - Relación consultations.appointment_id → appointments.patient_id:
 *   la lista de "Cita relacionada" solo muestra citas del paciente
 *   elegido, y elegir una cita selecciona a su paciente. El backend
 *   (ConsultationController@store) vuelve a validar esta relación.
 * ==========================================================
 */

import { bindPatientAppointmentFilter } from '../patient-appointment-filter';

document.addEventListener('DOMContentLoaded', function () {

    function bindCounter(fieldId, counterId) {
        const field = document.getElementById(fieldId);
        const counter = document.getElementById(counterId);

        if (!field || !counter) {
            return;
        }

        const update = () => { counter.textContent = field.value.length; };
        field.addEventListener('input', update);
        update();
    }

    bindCounter('observationsField', 'observationsCount');
    bindCounter('recommendationsField', 'recommendationsCount');

    /* ---------- Cita ↔ paciente ---------- */
    bindPatientAppointmentFilter(
        document.getElementById('patient_id'),
        document.getElementById('appointment_id')
    );
});
