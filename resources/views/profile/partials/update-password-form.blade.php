{{-- =========================================================
     CAMBIAR CONTRASEÑA (users.password, se guarda cifrada)
     Ruta: PUT password.update → Auth\PasswordController@update
     Errores en la bolsa "updatePassword".
     ========================================================= --}}
@php
    $pwdFields = [
        ['id' => 'update_password_current_password', 'name' => 'current_password', 'label' => 'Contraseña actual', 'autocomplete' => 'current-password', 'placeholder' => 'Tu contraseña de hoy'],
        ['id' => 'update_password_password', 'name' => 'password', 'label' => 'Nueva contraseña', 'autocomplete' => 'new-password', 'placeholder' => 'Mínimo 8 caracteres'],
        ['id' => 'update_password_password_confirmation', 'name' => 'password_confirmation', 'label' => 'Confirmar nueva contraseña', 'autocomplete' => 'new-password', 'placeholder' => 'Repite la nueva contraseña'],
    ];
@endphp

<section class="nl-settings">

    <header class="nl-settings-head">
        <span class="nl-settings-icon" aria-hidden="true"><i class="fas fa-key"></i></span>
        <div>
            <h3>Contraseña</h3>
            <p>Usa una contraseña larga y única para proteger los expedientes de tus pacientes.</p>
        </div>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="nl-settings-form" novalidate>
        @csrf
        @method('put')

        @foreach ($pwdFields as $field)
            @php $fieldError = $errors->updatePassword->first($field['name']); @endphp

            <div class="nl-field">
                <label for="{{ $field['id'] }}">{{ $field['label'] }} <span class="nl-req" aria-hidden="true">*</span></label>
                <div class="nl-input has-toggle {{ $fieldError ? 'has-error' : '' }}">
                    <i class="fas fa-lock" aria-hidden="true"></i>
                    <input
                        id="{{ $field['id'] }}"
                        name="{{ $field['name'] }}"
                        type="password"
                        required
                        autocomplete="{{ $field['autocomplete'] }}"
                        placeholder="{{ $field['placeholder'] }}"
                        @if ($field['name'] !== 'current_password') minlength="8" @endif
                        @if ($fieldError) aria-invalid="true" aria-describedby="{{ $field['id'] }}_error" @endif
                    >
                    <button type="button" class="nl-input-toggle" data-password-toggle="{{ $field['id'] }}"
                            aria-label="Mostrar contraseña" aria-pressed="false">
                        <i class="far fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
                @if ($fieldError)
                    <p class="nl-field-error" id="{{ $field['id'] }}_error"><i class="fas fa-circle-exclamation" aria-hidden="true"></i> {{ $fieldError }}</p>
                @endif
            </div>
        @endforeach

        <p class="nl-field-hint">
            Al menos 8 caracteres. Combinar letras, números y símbolos la hace más segura.
        </p>

        <div class="nl-settings-actions">
            <button type="submit" class="nl-btn nl-btn--primary" data-loading-text="Actualizando…">
                <i class="fas fa-shield-halved" aria-hidden="true"></i>
                Actualizar contraseña
            </button>

            @if (session('status') === 'password-updated')
                <span class="nl-saved" role="status" data-autohide>
                    <i class="fas fa-circle-check" aria-hidden="true"></i>
                    Contraseña actualizada
                </span>
            @endif
        </div>
    </form>

</section>
