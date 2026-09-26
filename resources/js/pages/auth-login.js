/**
 * ==========================================================
 * AUTH LOGIN
 * Alterna la visibilidad del campo de contraseña.
 * ==========================================================
 */

document.addEventListener('DOMContentLoaded', function () {

    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const passwordIcon = document.getElementById('passwordIcon');

    if (passwordInput && togglePassword && passwordIcon) {
        togglePassword.addEventListener('click', function () {
            const hidden = passwordInput.type === 'password';

            passwordInput.type = hidden ? 'text' : 'password';
            passwordIcon.className = hidden ? 'far fa-eye-slash' : 'far fa-eye';
        });
    }

});
