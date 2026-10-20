<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Iniciar sesión | NutriLife</title>

    {{-- Estilos de autenticación: tokens + login.css + login-light/dark.css.
         No tiene botón propio de tema: respeta el modo elegido en el resto
         del sitio (misma preferencia guardada). --}}
    @include('partials.theme-styles', ['section' => 'login'])

</head>


<body class="auth-login">

<div class="login-page">


    {{-- LUCES AMBIENTALES --}}
    <div class="ambient ambient-one"></div>
    <div class="ambient ambient-two"></div>
    <div class="ambient ambient-three"></div>


    {{-- HOJAS DECORATIVAS --}}
    <div class="background-leaf leaf-one">
        <i class="fas fa-leaf"></i>
    </div>

    <div class="background-leaf leaf-two">
        <i class="fas fa-leaf"></i>
    </div>

    <div class="background-leaf leaf-three">
        <i class="fas fa-seedling"></i>
    </div>



    <div class="login-shell">


        {{-- ====================================================
             PANEL DE MARCA
        ===================================================== --}}
        <section class="brand-panel">


            <div class="brand-panel-shine"></div>


            <div class="brand-top">

                <div class="brand-logo">
                    {{-- Logotipo NutriLife (versión según el modo claro/oscuro) --}}
                    <img src="{{ asset('images/brand/nutrilife-wordmark-light.png') }}" alt="NutriLife"
                         class="nl-logo nl-logo--on-light brand-logo-img" width="600" height="186">
                    <img src="{{ asset('images/brand/nutrilife-wordmark-dark.png') }}" alt="NutriLife"
                         class="nl-logo nl-logo--on-dark brand-logo-img" width="600" height="186">
                </div>


                <div class="brand-badge">

                    <span class="badge-dot"></span>

                    Gestión nutricional

                </div>

            </div>



            <div class="brand-content">

                <span class="brand-eyebrow">
                    TU CONSULTORIO, EN UN SOLO LUGAR
                </span>


                <h1>

                    Nutrición simple.

                    <span>
                        Gestión inteligente.
                    </span>

                </h1>


                <p>

                    Organiza pacientes, consultas, citas y progreso
                    nutricional desde una plataforma diseñada para
                    simplificar tu trabajo.

                </p>



                {{-- MINI CARDS --}}
                <div class="brand-features">


                    <div class="feature-glass">

                        <div class="feature-icon">
                            <i class="fas fa-user-friends"></i>
                        </div>

                        <div>

                            <strong>
                                Pacientes
                            </strong>

                            <span>
                                Todo organizado
                            </span>

                        </div>

                    </div>


                    <div class="feature-glass">

                        <div class="feature-icon">
                            <i class="far fa-calendar-check"></i>
                        </div>

                        <div>

                            <strong>
                                Agenda
                            </strong>

                            <span>
                                Citas bajo control
                            </span>

                        </div>

                    </div>


                    <div class="feature-glass">

                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>

                        <div>

                            <strong>
                                Progreso
                            </strong>

                            <span>
                                Seguimiento fácil
                            </span>

                        </div>

                    </div>


                </div>

            </div>



            {{-- DECORACIÓN CENTRAL --}}
            <div class="nutrition-orbit">

                <div class="orbit-ring orbit-ring-one"></div>

                <div class="orbit-ring orbit-ring-two"></div>


                <div class="orbit-main">

                    <img
                        src="{{ asset('images/brand/nutrilife-emblem-192.png') }}"
                        alt=""
                        class="orbit-emblem"
                        width="192" height="192"
                    >

                </div>


                <div class="orbit-item orbit-item-one">
                    🥑
                </div>

                <div class="orbit-item orbit-item-two">
                    🍋
                </div>

                <div class="orbit-item orbit-item-three">
                    🌿
                </div>

            </div>



            <div class="brand-footer">

                <span>
                    NutriLife
                </span>

                <span class="footer-dot"></span>

                <span>
                    Tu espacio de nutrición
                </span>

            </div>

        </section>





        {{-- ====================================================
             LOGIN
        ===================================================== --}}
        <section class="form-panel">


            <div class="login-glass">


                <div class="login-glass-shine"></div>


                <div class="login-header">


                    <div class="mobile-logo">
                        {{-- Logotipo NutriLife (versión según el modo claro/oscuro) --}}
                        <img src="{{ asset('images/brand/nutrilife-wordmark-light.png') }}" alt="NutriLife"
                             class="nl-logo nl-logo--on-light mobile-logo-img" width="600" height="186">
                        <img src="{{ asset('images/brand/nutrilife-wordmark-dark.png') }}" alt="NutriLife"
                             class="nl-logo nl-logo--on-dark mobile-logo-img" width="600" height="186">
                    </div>


                    <span class="welcome-label">
                        BIENVENIDO DE NUEVO
                    </span>


                    <h2>
                        Inicia sesión
                    </h2>


                    <p>
                        Ingresa tus datos para continuar a tu consultorio.
                    </p>

                </div>



                {{-- AVISOS (p. ej. "Cerraste sesión correctamente") --}}
                @if (session('status'))
                    <div class="success-message" role="status">
                        <div class="success-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <div>
                            <strong>{{ session('status') }}</strong>
                        </div>
                    </div>
                @endif

                {{-- ERRORES --}}
                @if ($errors->any())

                    <div class="error-message">

                        <div class="error-icon">

                            <i class="fas fa-exclamation"></i>

                        </div>


                        <div>

                            <strong>
                                No pudimos iniciar sesión
                            </strong>

                            @foreach ($errors->all() as $error)

                                <span>
                                    {{ $error }}
                                </span>

                            @endforeach

                        </div>

                    </div>

                @endif



                <form
                    method="POST"
                    action="{{ route('login') }}"
                    class="login-form"
                >

                    @csrf



                    {{-- EMAIL --}}
                    <div class="form-group">

                        <label for="email">

                            Correo electrónico

                        </label>


                        <div class="input-glass">

                            <div class="input-icon">

                                <i class="far fa-envelope"></i>

                            </div>


                            <input

                                id="email"

                                type="email"

                                name="email"

                                value="{{ old('email') }}"

                                placeholder="nutriologo@correo.com"

                                required

                                autofocus

                                autocomplete="username"

                            >

                        </div>

                    </div>





                    {{-- PASSWORD --}}
                    <div class="form-group">

                        <label for="password">

                            Contraseña

                        </label>


                        <div class="input-glass">

                            <div class="input-icon">

                                <i class="fas fa-lock"></i>

                            </div>


                            <input

                                id="password"

                                type="password"

                                name="password"

                                placeholder="Ingresa tu contraseña"

                                required

                                autocomplete="current-password"

                            >


                            <button

                                type="button"

                                class="toggle-password"

                                id="togglePassword"

                                aria-label="Mostrar contraseña"

                            >

                                <i
                                    class="far fa-eye"
                                    id="passwordIcon"
                                ></i>

                            </button>

                        </div>

                    </div>





                    {{-- OPCIONES --}}
                    <div class="login-options">


                        <label class="remember">

                            <input
                                type="checkbox"
                                name="remember"
                            >

                            <span class="custom-checkbox">

                                <i class="fas fa-check"></i>

                            </span>

                            <span>
                                Recordarme
                            </span>

                        </label>



                        @if (Route::has('password.request'))

                            <a
                                href="{{ route('password.request') }}"
                                class="forgot-password"
                            >

                                ¿Olvidaste tu contraseña?

                            </a>

                        @endif


                    </div>





                    {{-- BOTÓN --}}
                    <button
                        type="submit"
                        class="btn-login"
                    >

                        <span>
                            Iniciar sesión
                        </span>

                        <div class="login-arrow">

                            <i class="fas fa-arrow-right"></i>

                        </div>

                    </button>


                </form>



                <div class="login-security">

                    <i class="fas fa-shield-alt"></i>

                    <span>
                        Acceso seguro a tu información nutricional
                    </span>

                </div>


            </div>


            <div class="copyright">

                © {{ date('Y') }} NutriLife

                <span>•</span>

                Gestión nutricional

            </div>


        </section>


    </div>

</div>



{{-- Mostrar/ocultar contraseña — ver resources/js/pages/auth-login.js --}}
@vite(['resources/js/pages/auth-login.js'])


</body>

</html>