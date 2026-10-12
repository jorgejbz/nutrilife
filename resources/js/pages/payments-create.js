/**
 * ==========================================================
 * PAYMENTS CREATE
 * Relación payments.appointment_id → appointments.patient_id:
 * la lista de "Cita relacionada" solo muestra citas del paciente
 * elegido. El backend (PaymentController@store) vuelve a validarlo.
 * ==========================================================
 */

import { bindPatientAppointmentFilter } from '../patient-appointment-filter';

document.addEventListener('DOMContentLoaded', function () {
    bindPatientAppointmentFilter(
        document.getElementById('patient_id'),
        document.getElementById('appointment_id')
    );
});
