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

                <a href="#planes">
                    Planes
                </a>

                <a href="#faq">
                    FAQ
                </a>

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
            >

                <i class="fas fa-bars"></i>

            </button>


        </div>

    </nav>



    {{-- CONTENIDO --}}

    <main>

        @yield('content')

    </main>



    {{-- Menú móvil y navbar en scroll — ver resources/js/pages/marketing.js --}}
    @vite(['resources/js/pages/marketing.js'])


    @yield('js')

</body>

</html>