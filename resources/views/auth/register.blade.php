<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Crear cuenta | NutriLife</title>

    {{-- Estilos de autenticación: tokens + public/css/login/* + temas + responsive (config/stylesheets.php).
         El CSS que antes estaba aquí en un <style> inline (sin modo claro)
         ahora vive en public/css/login/register.css. --}}
    @include('partials.theme-styles', ['section' => 'login'])
</head>


<body class="auth-register">

<div class="register-page">

    <div class="register-wrapper">

        {{-- ==========================================
             PANEL IZQUIERDO
        =========================================== --}}
        <section class="register-info">

            <a
                href="{{ url('/') }}"
                class="register-logo"
            >

                {{-- Logotipo NutriLife (versión según el modo claro/oscuro) --}}
                <img src="{{ asset('images/brand/nutrilife-wordmark-light.png') }}" alt="NutriLife"
                     class="nl-logo nl-logo--on-light register-logo-img" width="600" height="186">
                <img src="{{ asset('images/brand/nutrilife-wordmark-dark.png') }}" alt="NutriLife"
                     class="nl-logo nl-logo--on-dark register-logo-img" width="600" height="186">

            </a>


            <div class="register-info-content">

                <div class="register-badge">
                    <i class="fas fa-leaf" aria-hidden="true"></i> Plataforma para profesionales de la nutrición
                </div>

                <h1>
                    Empieza a gestionar
                    <span>tu consulta.</span>
                </h1>

                <p class="register-info-description">
                    Organiza pacientes, citas, evaluaciones,
                    progreso y pagos desde una sola plataforma
                    diseñada para profesionales de la nutrición.
                </p>


                <div class="register-benefits">

                    <div class="register-benefit">

                        <div class="benefit-check">
                            ✓
                        </div>

                        7 días de prueba gratuita

                    </div>


                    <div class="register-benefit">

                        <div class="benefit-check">
                            ✓
                        </div>

                        Sin tarjeta de crédito

                    </div>


                    <div class="register-benefit">

                        <div class="benefit-check">
                            ✓
                        </div>

                        Tu información centralizada

                    </div>

                </div>

            </div>


            <div class="register-info-footer">
                © {{ date('Y') }} NutriLife · Gestión para nutriólogos
            </div>

        </section>



        {{-- ==========================================
             FORMULARIO
        =========================================== --}}
        <section class="register-form-side">

            <div class="form-heading">

                <span>
                    CREA TU CUENTA
                </span>

                <h2>
                    Comienza gratis.
                </h2>

                <p>
                    Crea tu espacio de trabajo y comienza
                    a organizar tu consulta.
                </p>

            </div>


            {{-- =========================================================
                 Registro de nutriólogo
                 Datos de la tabla `users`: name, email, password,
                 professional_license (cédula) y degree_number (título).
                 El servidor asigna role_id = 1 (nutriólogo), genera
                 access_code y deja la cuenta pendiente de activación.
                 ========================================================= --}}
            <form
                method="POST"
                action="{{ route('register') }}"
                id="registerForm"
                novalidate
            >

                @csrf

                <p class="form-required-note">
                    <span class="required-mark" aria-hidden="true">*</span> Todos los campos son obligatorios.
                </p>

                {{-- NOMBRE (users.name) --}}
                <div class="form-group">
                    <label class="form-label" for="name">
                        Nombre completo <span class="required-mark" aria-hidden="true">*</span>
                    </label>
                    <div class="input-wrapper @error('name') has-error @enderror">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                        <input
                            id="name"
                            class="form-input"
                            type="text"
                            name="name"
                            maxlength="255"
                            value="{{ old('name') }}"
                            required
                            autofocus
                            autocomplete="name"
                            placeholder="Ej. Andrea López Martínez"
                            @error('name') aria-invalid="true" aria-describedby="name_error" @enderror
                        >
                    </div>
                    <small class="form-hint">Así aparecerá tu nombre en el panel.</small>
                    @error('name')
                        <div class="form-error" id="name_error" role="alert">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- CORREO (users.email, UNIQUE) --}}
                <div class="form-group">
                    <label class="form-label" for="email">
                        Correo electrónico <span class="required-mark" aria-hidden="true">*</span>
                    </label>
                    <div class="input-wrapper @error('email') has-error @enderror">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                        <input
                            id="email"
                            class="form-input"
                            type="email"
                            name="email"
                            maxlength="255"
                            value="{{ old('email') }}"
                            required
                            autocomplete="email"
                            inputmode="email"
                            placeholder="correo@ejemplo.com"
                            @error('email') aria-invalid="true" aria-describedby="email_error" @enderror
                        >
                    </div>
                    <small class="form-hint">Con este correo iniciarás sesión.</small>
                    @error('email')
                        <div class="form-error" id="email_error" role="alert">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- DATOS PROFESIONALES (users.professional_license / degree_number)
                     Solo se valida el formato; no se consultan con la SEP. --}}
                <div class="register-pro-grid">
                    <div class="form-group">
                        <label class="form-label" for="professional_license">
                            Cédula profesional <span class="required-mark" aria-hidden="true">*</span>
                        </label>
                        <div class="input-wrapper @error('professional_license') has-error @enderror">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M6 16c.6-1.4 1.8-2 3-2s2.4.6 3 2M14 10h4M14 13h3"/></svg>
                            <input
                                id="professional_license"
                                class="form-input"
                                type="text"
                                name="professional_license"
                                value="{{ old('professional_license') }}"
                                required
                                inputmode="numeric"
                                pattern="\d{7,8}"
                                minlength="7"
                                maxlength="8"
                                autocomplete="off"
                                placeholder="Ej. 12345678"
                                aria-describedby="professional_license_hint @error('professional_license') professional_license_error @enderror"
                                @error('professional_license') aria-invalid="true" @enderror
                            >
                        </div>
                        <small class="form-hint" id="professional_license_hint">7 u 8 dígitos, sin guiones.</small>
                        @error('professional_license')
                            <div class="form-error" id="professional_license_error" role="alert">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="degree_number">
                            Número de título <span class="required-mark" aria-hidden="true">*</span>
                        </label>
                        <div class="input-wrapper @error('degree_number') has-error @enderror">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M22 9 12 4 2 9l10 5 10-5Z"/><path d="M6 11v5c3 2 9 2 12 0v-5"/></svg>
                            <input
                                id="degree_number"
                                class="form-input"
                                type="text"
                                name="degree_number"
                                value="{{ old('degree_number') }}"
                                required
                                minlength="3"
                                maxlength="50"
                                autocomplete="off"
                                autocapitalize="characters"
                                placeholder="Ej. LN-2019-04582"
                                aria-describedby="degree_number_hint @error('degree_number') degree_number_error @enderror"
                                @error('degree_number') aria-invalid="true" @enderror
                            >
                        </div>
                        <small class="form-hint" id="degree_number_hint">Como aparece en tu título profesional.</small>
                        @error('degree_number')
                            <div class="form-error" id="degree_number_error" role="alert">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>

                {{-- CONTRASEÑA (users.password, se guarda cifrada) --}}
                <div class="form-group">
                    <label class="form-label" for="password">
                        Contraseña <span class="required-mark" aria-hidden="true">*</span>
                    </label>
                    <div class="input-wrapper has-toggle @error('password') has-error @enderror">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        <input
                            id="password"
                            class="form-input"
                            type="password"
                            name="password"
                            minlength="8"
                            required
                            autocomplete="new-password"
                            placeholder="Mínimo 8 caracteres"
                            aria-describedby="password_hint @error('password') password_error @enderror"
                            @error('password') aria-invalid="true" @enderror
                        >
                        <button type="button" class="password-toggle" data-password-toggle="password" aria-label="Mostrar contraseña" aria-pressed="false">
                            <i class="far fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="password-strength" id="passwordStrength" data-level="0" aria-live="polite">
                        <div class="password-strength-bar"><span></span><span></span><span></span><span></span></div>
                        <span class="password-strength-label" id="passwordStrengthLabel"></span>
                    </div>

                    <small class="form-hint" id="password_hint">
                        Usa al menos 8 caracteres. Combinar letras, números y símbolos la hace más segura.
                    </small>
                    @error('password')
                        <div class="form-error" id="password_error" role="alert">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- CONFIRMACIÓN (solo validación, no se guarda) --}}
                <div class="form-group">
                    <label class="form-label" for="password_confirmation">
                        Confirmar contraseña <span class="required-mark" aria-hidden="true">*</span>
                    </label>
                    <div class="input-wrapper has-toggle @error('password_confirmation') has-error @enderror">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        <input
                            id="password_confirmation"
                            class="form-input"
                            type="password"
                            name="password_confirmation"
                            minlength="8"
                            required
                            autocomplete="new-password"
                            placeholder="Repite tu contraseña"
                            aria-describedby="password_match"
                        >
                        <button type="button" class="password-toggle" data-password-toggle="password_confirmation" aria-label="Mostrar contraseña" aria-pressed="false">
                            <i class="far fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <small class="form-hint password-match" id="password_match" aria-live="polite"></small>
                    @error('password_confirmation')
                        <div class="form-error" id="password_confirmation_error" role="alert">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- Qué ocurre al crear la cuenta (datos reales de la BD) --}}
                <div class="register-next">
                    <p class="register-next-title">
                        <i class="fas fa-circle-info" aria-hidden="true"></i>
                        Al crear tu cuenta
                    </p>
                    <ul>
                        <li>Prepararemos tu cuenta; en unos minutos podrás iniciar sesión.</li>
                        <li>Tu prueba gratuita de {{ config('nutrilife.trial_days') }} días empieza la primera vez que entres.</li>
                        <li>Recibirás un <strong>código de acceso de 6 dígitos</strong> para que tus pacientes entren a la app móvil.</li>
                        <li>Podrás completar y editar tus datos después desde <strong>Mi perfil</strong>.</li>
                    </ul>
                </div>

                <button type="submit" class="register-button" data-loading-text="Creando tu cuenta…">
                    Crear mi cuenta
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M5 12h14"/>
                        <path d="m13 6 6 6-6 6"/>
                    </svg>
                </button>

                <div class="login-text">
                    ¿Ya tienes una cuenta?
                    <a href="{{ route('login') }}">Inicia sesión</a>
                </div>

                <div class="register-security">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    Tu contraseña se guarda cifrada.
                </div>

            </form>

        </section>

    </div>

</div>

    @vite(['resources/js/pages/auth-register.js'])
</body>
</html>