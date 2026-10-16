/**
 * ==========================================================
 * REGISTRO DE NUTRIÓLOGOS (resources/views/auth/register.blade.php)
 *
 * - Validación en el navegador con mensajes en español, con las
 *   mismas reglas que RegisteredUserController@store
 *   (name ≤ 255, email válido ≤ 255, contraseña ≥ 8, confirmación).
 *   El servidor vuelve a validar todo; esto solo da respuesta rápida.
 * - Mostrar / ocultar contraseña.
 * - Indicador de seguridad de la contraseña (informativo).
 * - Aviso en vivo de si la confirmación coincide.
 *
 * El formulario tiene `novalidate`: si este archivo no cargara, el
 * servidor seguiría validando y mostrando los errores.
 * ==========================================================
 */

document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('registerForm');

    if (!form) {
        return;
    }

    const fields = {
        name: document.getElementById('name'),
        email: document.getElementById('email'),
        password: document.getElementById('password'),
        password_confirmation: document.getElementById('password_confirmation'),
    };

    const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

    /* ---------- Reglas (espejo del backend) ---------- */
    function validate(name) {
        const value = fields[name].value;

        switch (name) {
            case 'name':
                if (!value.trim()) return 'Escribe tu nombre completo.';
                if (value.trim().length > 255) return 'El nombre no puede superar 255 caracteres.';
                return '';

            case 'email':
                if (!value.trim()) return 'Escribe tu correo electrónico.';
                if (!EMAIL_PATTERN.test(value.trim())) return 'Escribe un correo válido, por ejemplo nombre@dominio.com.';
                if (value.trim().length > 255) return 'El correo no puede superar 255 caracteres.';
                return '';

            case 'password':
                if (!value) return 'Escribe una contraseña.';
                if (value.length < 8) return 'La contraseña debe tener al menos 8 caracteres.';
                return '';

            case 'password_confirmation':
                if (!value) return 'Confirma tu contraseña.';
                if (value !== fields.password.value) return 'Las contraseñas no coinciden.';
                return '';
        }

        return '';
    }

    /* ---------- Mostrar / quitar error de un campo ---------- */
    function setError(name, message) {
        const input = fields[name];
        const group = input.closest('.form-group');
        const wrapper = input.closest('.input-wrapper');
        let box = document.getElementById(name + '_error');

        if (message) {
            if (!box) {
                box = document.createElement('div');
                box.className = 'form-error';
                box.id = name + '_error';
                box.setAttribute('role', 'alert');
                group.appendChild(box);
            }
            box.textContent = message;
            input.setAttribute('aria-invalid', 'true');
            wrapper.classList.add('has-error');
        } else {
            if (box) {
                box.remove();
            }
            input.removeAttribute('aria-invalid');
            wrapper.classList.remove('has-error');
        }
    }

    /* Los errores aparecen al salir del campo (no mientras se escribe por
       primera vez) y se actualizan en vivo una vez mostrados. */
    Object.keys(fields).forEach(function (name) {
        const input = fields[name];

        input.addEventListener('blur', function () {
            if (input.value !== '') {
                setError(name, validate(name));
            }
        });

        input.addEventListener('input', function () {
            if (input.getAttribute('aria-invalid') === 'true') {
                setError(name, validate(name));
            }
        });
    });

    form.addEventListener('submit', function (event) {
        let firstInvalid = null;

        Object.keys(fields).forEach(function (name) {
            const message = validate(name);
            setError(name, message);
            if (message && !firstInvalid) {
                firstInvalid = fields[name];
            }
        });

        if (firstInvalid) {
            event.preventDefault();
            firstInvalid.focus();
        }
    });

    /* ---------- Mostrar / ocultar contraseña ---------- */
    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        const input = document.getElementById(button.dataset.passwordToggle);
        const icon = button.querySelector('i');

        button.addEventListener('click', function () {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(show));
            button.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
            if (icon) {
                icon.className = show ? 'far fa-eye-slash' : 'far fa-eye';
            }
            input.focus();
        });
    });

    /* ---------- Seguridad de la contraseña (orientativa) ---------- */
    const strength = document.getElementById('passwordStrength');
    const strengthLabel = document.getElementById('passwordStrengthLabel');
    const LABELS = ['', 'Débil', 'Aceptable', 'Buena', 'Muy segura'];

    function score(value) {
        if (!value) return 0;
        if (value.length < 8) return 1;

        let points = 1;
        if (/[a-záéíóúñ]/i.test(value) && /\d/.test(value)) points++;
        if (/[^a-z0-9áéíóúñ]/i.test(value) || /[A-ZÁÉÍÓÚÑ]/.test(value) && /[a-záéíóúñ]/.test(value)) points++;
        if (value.length >= 12) points++;

        return Math.min(points, 4);
    }

    /* ---------- ¿Coincide la confirmación? ---------- */
    const matchHint = document.getElementById('password_match');

    function updateMatch() {
        const confirmation = fields.password_confirmation.value;

        if (!confirmation) {
            matchHint.textContent = '';
            matchHint.dataset.state = '';
            return;
        }

        const ok = confirmation === fields.password.value;
        matchHint.textContent = ok ? 'Las contraseñas coinciden.' : 'Las contraseñas aún no coinciden.';
        matchHint.dataset.state = ok ? 'ok' : 'pending';
    }

    fields.password.addEventListener('input', function () {
        const level = score(fields.password.value);
        strength.dataset.level = String(level);
        strengthLabel.textContent = LABELS[level];
        updateMatch();
    });

    fields.password_confirmation.addEventListener('input', updateMatch);
});
