/**
 * ==========================================================
 * APPOINTMENTS CREATE
 * Actualiza el panel de vista previa (paciente, fecha, motivo,
 * notas y estado) mientras se llena el formulario de la cita.
 * ==========================================================
 */

document.addEventListener('DOMContentLoaded', function () {

    const patientSelect = document.getElementById('patient_id');
    const appointmentAt = document.getElementById('appointment_at');
    const reason = document.getElementById('reason');
    const status = document.getElementById('status');
    const notes = document.getElementById('notes');

    if (!patientSelect) {
        return;
    }

    const months = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
    const fullMonths = [
        'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
        'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
    ];

    function updatePatient() {
        const option = patientSelect.options[patientSelect.selectedIndex];
        const name = option.dataset.name || '';
        const email = option.dataset.email || '';
        const phone = option.dataset.phone || '';

        document.getElementById('previewPatient').textContent = name || 'Sin seleccionar';
        document.getElementById('previewAvatar').textContent = name ? name.charAt(0).toUpperCase() : '?';

        let contact = email || phone;
        if (email && phone) {
            contact = phone + ' · ' + email;
        }

        document.getElementById('previewContact').textContent = contact || 'Sin información de contacto';
    }

    function updateDate() {
        if (!appointmentAt.value) {
            document.getElementById('previewDay').textContent = '—';
            document.getElementById('previewMonth').textContent = 'MES';
            document.getElementById('previewDateText').textContent = 'Sin fecha seleccionada';
            document.getElementById('previewTime').textContent = 'Selecciona el horario';
            return;
        }

        const date = new Date(appointmentAt.value);
        if (isNaN(date.getTime())) {
            return;
        }

        document.getElementById('previewDay').textContent = String(date.getDate()).padStart(2, '0');
        document.getElementById('previewMonth').textContent = months[date.getMonth()];
        document.getElementById('previewDateText').textContent =
            date.getDate() + ' de ' + fullMonths[date.getMonth()] + ' de ' + date.getFullYear();

        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        document.getElementById('previewTime').textContent = hours + ':' + minutes + ' hrs';
    }

    function updateReason() {
        document.getElementById('previewReason').textContent =
            reason.value.trim() || 'Sin motivo registrado';
    }

    function updateNotes() {
        document.getElementById('previewNotes').textContent =
            notes.value.trim() || 'Sin notas adicionales.';
        document.getElementById('notesCount').textContent = notes.value.length;
    }

    function updateStatus() {
        const preview = document.getElementById('previewStatus');
        const previewText = document.getElementById('previewStatusText');
        const currentStatus = status.value;

        preview.classList.remove('pending', 'confirmed', 'completed', 'cancelled');

        switch (currentStatus) {
            case 'Confirmada':
                preview.classList.add('confirmed');
                break;
            case 'Completada':
                preview.classList.add('completed');
                break;
            case 'Cancelada':
                preview.classList.add('cancelled');
                break;
            default:
                preview.classList.add('pending');
                break;
        }

        previewText.textContent = currentStatus;
    }

    patientSelect.addEventListener('change', updatePatient);
    appointmentAt.addEventListener('input', updateDate);
    reason.addEventListener('input', updateReason);
    status.addEventListener('change', updateStatus);
    notes.addEventListener('input', updateNotes);

    updatePatient();
    updateDate();
    updateReason();
    updateStatus();
    updateNotes();

});
