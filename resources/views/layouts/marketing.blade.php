<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        @yield('title', 'NutriLife')
    </title>

    <meta
        name="description"
        content="NutriLife te ayuda a gestionar pacientes, citas, progreso, pagos y planes nutricionales desde un solo lugar."
    >

    {{-- Estilos del sitio público: tokens + app.css + app-light/dark.css,
         anti-parpadeo del tema, iconos y botón de tema --}}
    @include('partials.theme-styles', ['section' => 'app'])

</head>


<body>


    {{-- ============================================
         NAVBAR
    ============================================= --}}

    <nav class="landing-navbar">

        <div class="landing-container navbar-content">


            {{-- LOGO --}}

            <a
                href="{{ route('landing') }}"
                class="landing-logo"
                aria-label="NutriLife — inicio"
            >

                {{-- Logotipo NutriLife: se muestra la versión adecuada al modo --}}
                <img
                    src="{{ asset('images/brand/nutrilife-wordmark-light.png') }}"
                    alt="NutriLife"
                    class="nl-logo nl-logo--on-light"
                    width="600" height="186"
                >
                <img
                    src="{{ asset('images/brand/nutrilife-wordmark-dark.png') }}"
                    alt="NutriLife"
                    class="nl-logo nl-logo--on-dark"
                    width="600" height="186"
                >

            </a>



            {{-- LINKS --}}

            <div
                class="landing-nav-links"
                id="landingNav"
                aria-label="Secciones"
            >

                <a href="#inicio">
                    Inicio
                </a>

                <a href="#funciones">
                    Funciones
                </a>

                <a href="#como-funciona">
                    Cómo funciona
                </a>

                <a href="#pacientes">

                    App pacientes

                </a>


                <a href="#planes">
                    Planes
                </a>

                <a href="#faq">
                    FAQ
                </a>

                {{-- Solo en menú móvil: en pantallas chicas "Iniciar sesión"
                     se ocultaba de la barra y no había forma de entrar. --}}
                <div class="nav-mobile-account">
                    @auth
                        <a href="{{ route('dashboard.index') }}" class="nav-login">Ir al panel</a>
                    @else
                        <a href="{{ route('login') }}" class="nav-login">Iniciar sesión</a>
                        <a href="{{ route('register') }}" class="nav-trial">Crear cuenta gratis</a>
                    @endauth
                </div>

            </div>



            {{-- ACCIONES --}}

            <div class="landing-nav-actions">

                @include('partials.theme-toggle')

                @auth

                    <a
                        href="{{ route('dashboard.index') }}"
                        class="nav-login"
                    >
                        Ir al panel
                    </a>

                @else

                    <a
                        href="{{ route('login') }}"
                        class="nav-login"
                    >
                        Iniciar sesión
                    </a>


                    <a
                        href="{{ route('register') }}"
                        class="nav-trial"
                    >

                        Prueba gratis

                        <i class="fas fa-arrow-right"></i>

                    </a>

                @endauth

            </div>



            {{-- MÓVIL --}}

            <button
                class="mobile-menu-button"
                id="mobileMenuButton"
                type="button"
                aria-controls="landingNav"
                aria-expanded="false"
                aria-label="Abrir menú"
            >
                <i class="fas fa-bars" aria-hidden="true"></i>
            </button>


        </div>

    </nav>



    {{-- CONTENIDO --}}

    <main>

        @yield('content')

    </main>

    {{-- =========================================================
         PIE DE PÁGINA (antes no existía)
         ========================================================= --}}
    <footer class="landing-footer">
        <div class="landing-container">
            <div class="landing-footer-grid">

                <div class="landing-footer-brand">
                    <img src="{{ asset('images/brand/nutrilife-wordmark-light.png') }}" alt="NutriLife"
                         class="nl-logo nl-logo--on-light" width="600" height="186">
                    <img src="{{ asset('images/brand/nutrilife-wordmark-dark.png') }}" alt="NutriLife"
                         class="nl-logo nl-logo--on-dark" width="600" height="186">
                    <p>
                        La plataforma para nutriólogos que quieren organizar pacientes,
                        citas, evaluaciones, progreso y pagos en un solo lugar.
                    </p>
                </div>

                <nav aria-label="Producto">
                    <h3>Producto</h3>
                    <ul>
                        <li><a href="{{ route('landing') }}#funciones">Funciones</a></li>
                        <li><a href="{{ route('landing') }}#como-funciona">Cómo funciona</a></li>
                        <li><a href="{{ route('landing') }}#pacientes">App para pacientes</a></li>
                        <li><a href="{{ route('landing') }}#planes">Planes</a></li>
                    </ul>
                </nav>

                <nav aria-label="Cuenta">
                    <h3>Cuenta</h3>
                    <ul>
                        @auth
                            <li><a href="{{ route('dashboard.index') }}">Ir al panel</a></li>
                            <li><a href="{{ route('profile.edit') }}">Mi perfil</a></li>
                        @else
                            <li><a href="{{ route('login') }}">Iniciar sesión</a></li>
                            <li><a href="{{ route('register') }}">Crear cuenta</a></li>
                            <li><a href="{{ route('password.request') }}">Recuperar contraseña</a></li>
                        @endauth
                    </ul>
                </nav>

                <nav aria-label="Ayuda">
                    <h3>Ayuda</h3>
                    <ul>
                        <li><a href="{{ route('landing') }}#faq">Preguntas frecuentes</a></li>
                        @if (config('nutrilife.contact_email'))
                            <li><a href="mailto:{{ config('nutrilife.contact_email') }}">{{ config('nutrilife.contact_email') }}</a></li>
                        @endif
                    </ul>
                </nav>

            </div>

            <div class="landing-footer-bottom">
                <span>© {{ date('Y') }} NutriLife · Tu clínica de nutrición digital</span>
                <span>Hecho para profesionales de la nutrición</span>
            </div>
        </div>
    </footer>



    {{-- Menú móvil y navbar en scroll — ver resources/js/pages/marketing.js --}}
    @vite(['resources/js/pages/marketing.js'])


    @yield('js')

</body>

</html>