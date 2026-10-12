/**
 * ==========================================================
 * MI PERFIL (resources/views/profile/edit.blade.php)
 * - Mostrar / ocultar contraseñas  ([data-password-toggle="id"])
 * - Diálogo de "Eliminar cuenta"   ([data-dialog-open] / [data-dialog-close])
 *   Se abre solo al cargar si el servidor devolvió un error
 *   (atributo data-open-on-load en el <dialog>).
 * - Los avisos "Cambios guardados" se ocultan tras unos segundos.
 * Sustituye a Alpine.js, que solo se usaba para el modal de Breeze.
 * ==========================================================
 */

document.addEventListener('DOMContentLoaded', function () {

    /* ---------- Mostrar / ocultar contraseña ---------- */
    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        const input = document.getElementById(button.dataset.passwordToggle);
        const icon = button.querySelector('i');

        if (!input) {
            return;
        }

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

    /* ---------- Diálogos ---------- */
    document.querySelectorAll('[data-dialog-open]').forEach(function (button) {
        const dialog = document.getElementById(button.dataset.dialogOpen);

        if (!dialog || typeof dialog.showModal !== 'function') {
            return;
        }

        button.addEventListener('click', function () {
            if (!button.disabled) {
                dialog.showModal();
                dialog.querySelector('input:not([type="hidden"])')?.focus();
            }
        });
    });

    document.querySelectorAll('dialog.nl-dialog').forEach(function (dialog) {
        dialog.querySelectorAll('[data-dialog-close]').forEach(function (button) {
            button.addEventListener('click', function () {
                dialog.close();
            });
        });

        // Clic fuera del contenido cierra el diálogo
        dialog.addEventListener('click', function (event) {
            if (event.target === dialog) {
                dialog.close();
            }
        });

        if (dialog.hasAttribute('data-open-on-load') && typeof dialog.showModal === 'function') {
            dialog.showModal();
            dialog.querySelector('input:not([type="hidden"])')?.focus();
        }
    });

    /* ---------- Avisos "Cambios guardados" ---------- */
    document.querySelectorAll('[data-autohide]').forEach(function (notice) {
        setTimeout(function () {
            notice.classList.add('is-hiding');
            setTimeout(function () { notice.remove(); }, 400);
        }, 4000);
    });
});
