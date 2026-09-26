/**
 * ==========================================================
 * PATIENTS EDIT
 * Actualiza la vista previa (nombre, contacto, objetivo y
 * estado activo/inactivo) mientras se edita el formulario.
 * ==========================================================
 */

document.addEventListener('DOMContentLoaded', function () {

    const firstName = document.getElementById('first_name');
    const lastName = document.getElementById('last_name');
    const occupation = document.getElementById('occupation');
    const phone = document.getElementById('phone');
    const email = document.getElementById('email');
    const goal = document.getElementById('goal');
    const active = document.querySelector('input[name="active"][type="checkbox"]');

    if (!firstName) {
        return;
    }

    function updatePreview() {
        const name = firstName.value.trim();
        const lastname = lastName.value.trim();

        document.getElementById('previewName').textContent =
            (name + ' ' + lastname).trim() || 'Paciente';

        document.getElementById('previewInitial').textContent =
            name ? name.charAt(0).toUpperCase() : 'P';

        document.getElementById('previewOccupation').textContent =
            occupation.value.trim() || 'Sin ocupación';

        document.getElementById('previewPhone').textContent =
            phone.value.trim() || 'Sin teléfono';

        document.getElementById('previewEmail').textContent =
            email.value.trim() || 'Sin correo';

        document.getElementById('previewGoal').textContent =
            goal.value.trim() || 'Sin objetivo';

        document.getElementById('goalCount').textContent =
            goal.value.length;

        const state = document.getElementById('previewState');
        const stateText = document.getElementById('previewStateText');
        const statusText = document.getElementById('statusText');

        if (active.checked) {
            state.classList.remove('inactive');
            state.classList.add('active');
            stateText.textContent = 'Paciente activo';
            statusText.textContent = 'Paciente activo';
        } else {
            state.classList.remove('active');
            state.classList.add('inactive');
            stateText.textContent = 'Paciente inactivo';
            statusText.textContent = 'Paciente inactivo';
        }
    }

    [firstName, lastName, occupation, phone, email, goal, active].forEach(function (field) {
        field.addEventListener('input', updatePreview);
        field.addEventListener('change', updatePreview);
    });

    updatePreview();

});
