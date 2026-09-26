<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Crear cuenta | NutriLife</title>

    {{-- Estilos de autenticación: tokens + login.css + login-light/dark.css.
         El CSS que antes estaba aquí en un <style> inline (sin modo claro)
         ahora vive en public/css/login.css, sección "2. REGISTRO". --}}
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
                    🌿 Plataforma para profesionales de la nutrición
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


            <form
                method="POST"
                action="{{ route('register') }}"
            >

                @csrf


                {{-- NOMBRE --}}
                <div class="form-group">

                    <label
                        class="form-label"
                        for="name"
                    >
                        Nombre completo
                    </label>

                    <div class="input-wrapper">

                        <svg
                            class="input-icon"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path d="M20 21a8 8 0 0 0-16 0"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>

                        <input
                            id="name"
                            class="form-input"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            autofocus
                            autocomplete="name"
                            placeholder="Ej. Andrea López"
                        >

                    </div>

                    @error('name')
                        <div class="form-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>



                {{-- EMAIL --}}
                <div class="form-group">

                    <label
                        class="form-label"
                        for="email"
                    >
                        Correo electrónico
                    </label>

                    <div class="input-wrapper">

                        <svg
                            class="input-icon"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <rect
                                x="3"
                                y="5"
                                width="18"
                                height="14"
                                rx="2"
                            />

                            <path d="m3 7 9 6 9-6"/>
                        </svg>

                        <input
                            id="email"
                            class="form-input"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autocomplete="username"
                            placeholder="correo@ejemplo.com"
                        >

                    </div>

                    @error('email')
                        <div class="form-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>



                {{-- PASSWORD --}}
                <div class="form-group">

                    <label
                        class="form-label"
                        for="password"
                    >
                        Contraseña
                    </label>

                    <div class="input-wrapper">

                        <svg
                            class="input-icon"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <rect
                                x="5"
                                y="10"
                                width="14"
                                height="10"
                                rx="2"
                            />

                            <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                        </svg>

                        <input
                            id="password"
                            class="form-input"
                            type="password"
                            name="password"
                            required
                            autocomplete="new-password"
                            placeholder="Mínimo 8 caracteres"
                        >

                    </div>

                    @error('password')
                        <div class="form-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>



                {{-- CONFIRMAR PASSWORD --}}
                <div class="form-group">

                    <label
                        class="form-label"
                        for="password_confirmation"
                    >
                        Confirmar contraseña
                    </label>

                    <div class="input-wrapper">

                        <svg
                            class="input-icon"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path d="M20 6 9 17l-5-5"/>
                        </svg>

                        <input
                            id="password_confirmation"
                            class="form-input"
                            type="password"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                            placeholder="Repite tu contraseña"
                        >

                    </div>

                    @error('password_confirmation')
                        <div class="form-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>



                {{-- BOTÓN --}}
                <button
                    type="submit"
                    class="register-button"
                >

                    Crear mi cuenta

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M5 12h14"/>
                        <path d="m13 6 6 6-6 6"/>
                    </svg>

                </button>


                <div class="login-text">

                    ¿Ya tienes una cuenta?

                    <a href="{{ route('login') }}">
                        Inicia sesión
                    </a>

                </div>


                <div class="register-security">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>

                    Tus datos están protegidos

                </div>

            </form>

        </section>

    </div>

</div>

</body>
</html>