{{-- =========================================================
     ELIMINAR CUENTA
     Ruta: DELETE perfil.destroy → ProfileController@destroy
     Errores en la bolsa "userDeletion".
     Una cuenta con pacientes, citas, consultas o pagos NO se puede
     eliminar (FK sin cascada; ver User::hasClinicalData): se avisa
     antes y el botón queda deshabilitado.
     La confirmación usa <dialog> nativo (resources/js/pages/profile-edit.js).
     ========================================================= --}}
@php
    $canDelete = $canDeleteAccount ?? true;
    $deleteError = $errors->userDeletion->first('password');
@endphp

<section class="nl-settings nl-settings--danger">

    <header class="nl-settings-head">
        <span class="nl-settings-icon" aria-hidden="true"><i class="fas fa-triangle-exclamation"></i></span>
        <div>
            <h3>Eliminar cuenta</h3>
            <p>
                Se borrarán tu cuenta, tu suscripción y tu historial de cobros.
                Esta acción no se puede deshacer.
            </p>
        </div>
    </header>

    @if (! $canDelete)
        <div class="nl-inline-notice nl-inline-notice--warn">
            <i class="fas fa-folder-open" aria-hidden="true"></i>
            <div>
                Tu cuenta tiene pacientes, citas, consultas o pagos registrados.
                Para proteger los expedientes clínicos no es posible eliminarla.
                Si necesitas darla de baja, contacta al equipo de NutriLife.
            </div>
        </div>
    @endif

    <div class="nl-settings-actions">
        <button
            type="button"
            class="nl-btn nl-btn--danger"
            data-dialog-open="deleteAccountDialog"
            @disabled(! $canDelete)
        >
            <i class="fas fa-trash-can" aria-hidden="true"></i>
            Eliminar mi cuenta
        </button>
    </div>

    <dialog
        id="deleteAccountDialog"
        class="nl-dialog"
        aria-labelledby="deleteAccountTitle"
        @if ($errors->userDeletion->isNotEmpty()) data-open-on-load @endif
    >
        <form method="post" action="{{ route('perfil.destroy') }}" class="nl-dialog-body" novalidate>
            @csrf
            @method('delete')

            <span class="nl-dialog-icon" aria-hidden="true"><i class="fas fa-trash-can"></i></span>

            <h3 id="deleteAccountTitle">¿Eliminar tu cuenta definitivamente?</h3>

            <p>
                Escribe tu contraseña para confirmar. Perderás el acceso a NutriLife
                y no podremos recuperar tu información.
            </p>

            <div class="nl-field">
                <label for="delete_password">Contraseña <span class="nl-req" aria-hidden="true">*</span></label>
                <div class="nl-input has-toggle {{ $deleteError ? 'has-error' : '' }}">
                    <i class="fas fa-lock" aria-hidden="true"></i>
                    <input
                        id="delete_password"
                        name="password"
                        type="password"
                        required
                        autocomplete="current-password"
                        placeholder="Tu contraseña"
                        @if ($deleteError) aria-invalid="true" aria-describedby="delete_password_error" @endif
                    >
                    <button type="button" class="nl-input-toggle" data-password-toggle="delete_password"
                            aria-label="Mostrar contraseña" aria-pressed="false">
                        <i class="far fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
                @if ($deleteError)
                    <p class="nl-field-error" id="delete_password_error"><i class="fas fa-circle-exclamation" aria-hidden="true"></i> {{ $deleteError }}</p>
                @endif
            </div>

            <div class="nl-dialog-actions">
                <button type="button" class="nl-btn nl-btn--ghost" data-dialog-close>
                    Cancelar
                </button>
                <button type="submit" class="nl-btn nl-btn--danger" data-loading-text="Eliminando…">
                    <i class="fas fa-trash-can" aria-hidden="true"></i>
                    Sí, eliminar mi cuenta
                </button>
            </div>
        </form>
    </dialog>

</section>
