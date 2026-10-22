{{-- =========================================================
     DATOS DE LA CUENTA (users.name, users.email)
     Ruta: PATCH perfil.update → ProfileController@update
     Validación: ProfileUpdateRequest (correo en minúsculas, único).
     Si cambia el correo, email_verified_at vuelve a null.
     ========================================================= --}}
<section class="nl-settings">

    <header class="nl-settings-head">
        <span class="nl-settings-icon" aria-hidden="true"><i class="far fa-id-card"></i></span>
        <div>
            <h3>Datos de la cuenta</h3>
            <p>{{ ($isClinic ?? false) ? 'El nombre de la clínica aparece en el panel y en los perfiles de tu equipo; con tu correo inicias sesión.' : 'Tu nombre aparece en el panel; con tu correo inicias sesión.' }}</p>
        </div>
    </header>

    {{-- Reenviar verificación (se envía desde el botón del aviso de abajo) --}}
    <form id="send-verification" method="post" action="{{ route('verification.send') }}" data-no-submit-guard>
        @csrf
    </form>

    <form method="post" action="{{ route('perfil.update') }}" class="nl-settings-form" novalidate>
        @csrf
        @method('patch')

        <div class="nl-field">
            <label for="name">{{ ($isClinic ?? false) ? 'Nombre de la clínica' : 'Nombre completo' }} <span class="nl-req" aria-hidden="true">*</span></label>
            <div class="nl-input @error('name') has-error @enderror">
                <i class="far fa-user" aria-hidden="true"></i>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name', $user->name) }}"
                    maxlength="255"
                    required
                    autocomplete="name"
                    placeholder="Ej. Dra. Andrea López Martínez"
                    @error('name') aria-invalid="true" aria-describedby="name_error" @enderror
                >
            </div>
            @error('name')
                <p class="nl-field-error" id="name_error"><i class="fas fa-circle-exclamation" aria-hidden="true"></i> {{ $message }}</p>
            @enderror
        </div>

        <div class="nl-field">
            <label for="email">Correo electrónico <span class="nl-req" aria-hidden="true">*</span></label>
            <div class="nl-input @error('email') has-error @enderror">
                <i class="far fa-envelope" aria-hidden="true"></i>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email', $user->email) }}"
                    maxlength="255"
                    required
                    autocomplete="username"
                    inputmode="email"
                    placeholder="correo@ejemplo.com"
                    @error('email') aria-invalid="true" aria-describedby="email_error" @enderror
                >
            </div>
            @error('email')
                <p class="nl-field-error" id="email_error"><i class="fas fa-circle-exclamation" aria-hidden="true"></i> {{ $message }}</p>
            @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="nl-inline-notice">
                    <i class="fas fa-envelope-circle-check" aria-hidden="true"></i>
                    <div>
                        Tu correo aún no está verificado.
                        <button form="send-verification" type="submit" class="nl-link-button">
                            Reenviar correo de verificación
                        </button>
                        @if (session('status') === 'verification-link-sent')
                            <span class="nl-inline-ok">Te enviamos un nuevo enlace de verificación.</span>
                        @endif
                    </div>
                </div>
            @endif

            <p class="nl-field-hint">Si cambias tu correo tendrás que verificarlo de nuevo.</p>
        </div>

        <div class="nl-settings-actions">
            <button type="submit" class="nl-btn nl-btn--primary" data-loading-text="Guardando…">
                <i class="fas fa-floppy-disk" aria-hidden="true"></i>
                Guardar cambios
            </button>

            @if (session('status') === 'profile-updated')
                <span class="nl-saved" role="status" data-autohide>
                    <i class="fas fa-circle-check" aria-hidden="true"></i>
                    Cambios guardados
                </span>
            @endif
        </div>
    </form>

</section>
