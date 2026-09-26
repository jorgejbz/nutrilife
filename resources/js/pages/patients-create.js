/**
 * ==========================================================
 * PATIENTS CREATE
 * Actualiza la vista previa del panel derecho en tiempo real
 * conforme el nutriólogo llena el formulario.
 * ==========================================================
 */

document.addEventListener('DOMContentLoaded', function () {

    const firstName = document.getElementById('first_name');
    const lastName = document.getElementById('last_name');
    const occupation = document.getElementById('occupation');
    const phone = document.getElementById('phone');
    const email = document.getElementById('email');
    const goal = document.getElementById('goal');

    const previewName = document.getElementById('previewName');
    const previewInitial = document.getElementById('previewInitial');
    const previewOccupation = document.getElementById('previewOccupation');
    const previewPhone = document.getElementById('previewPhone');
    const previewEmail = document.getElementById('previewEmail');
    const previewGoal = document.getElementById('previewGoal');
    const goalCount = document.getElementById('goalCount');

    if (!firstName) {
        return;
    }

    function updatePreview() {
        const name = firstName.value.trim();
        const lastname = lastName.value.trim();
        const fullName = (name + ' ' + lastname).trim();

        previewName.textContent = fullName || 'Nuevo paciente';
        previewInitial.textContent = name ? name.charAt(0).toUpperCase() : 'N';
        previewOccupation.textContent = occupation.value.trim() || 'Información pendiente';
        previewPhone.textContent = phone.value.trim() || 'Sin teléfono';
        previewEmail.textContent = email.value.trim() || 'Sin correo';
        previewGoal.textContent = goal.value.trim() || 'Sin objetivo';
        goalCount.textContent = goal.value.length;
    }

    [firstName, lastName, occupation, phone, email, goal].forEach(function (field) {
        field.addEventListener('input', updatePreview);
    });

    updatePreview();

});
