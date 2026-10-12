/**
 * ==========================================================
 * FILTRO CITA ↔ PACIENTE (compartido)
 *
 * Para formularios con un <select> de paciente (patient_id) y otro de
 * cita opcional (appointment_id) cuyas opciones llevan
 * data-patient-id="{{ $appointment->patient_id }}":
 *   - solo se muestran las citas del paciente elegido;
 *   - elegir una cita selecciona a su paciente.
 * El backend vuelve a validar la relación; esto solo evita errores.
 *
 * Usado por: pages/consultations-create.js, pages/payments-create.js
 * ==========================================================
 */

export function bindPatientAppointmentFilter(patientSelect, appointmentSelect, emptyLabel = 'Ninguna') {
    if (!patientSelect || !appointmentSelect) {
        return;
    }

    const options = Array.from(appointmentSelect.options).filter((option) => option.value !== '');

    function filter() {
        const patientId = patientSelect.value;
        let visible = 0;

        options.forEach(function (option) {
            const matches = !patientId || option.dataset.patientId === patientId;
            option.hidden = !matches;
            option.disabled = !matches;
            if (matches) {
                visible++;
            }
        });

        const selected = appointmentSelect.selectedOptions[0];
        if (selected && selected.value !== '' && selected.disabled) {
            appointmentSelect.value = '';
        }

        appointmentSelect.options[0].textContent = patientId && visible === 0
            ? 'Este paciente no tiene citas disponibles'
            : emptyLabel;
    }

    appointmentSelect.addEventListener('change', function () {
        const selected = appointmentSelect.selectedOptions[0];

        if (selected && selected.dataset.patientId && patientSelect.value !== selected.dataset.patientId) {
            patientSelect.value = selected.dataset.patientId;
            patientSelect.dispatchEvent(new Event('change'));
        }
    });

    patientSelect.addEventListener('change', filter);
    filter();
}
