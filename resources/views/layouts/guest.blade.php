{{-- =========================================================
     LAYOUT GUEST
     Recuperar / restablecer contraseña, verificar correo y
     confirmar contraseña. Antes usaba Tailwind (Breeze) con el
     logo de Laravel; ahora comparte los estilos del login
     (public/css/login/guest.css).
     ========================================================= --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'NutriLife') }}</title>

        @include('partials.theme-styles', ['section' => 'login'])
    </head>
    <body class="auth-guest">
        <main class="auth-guest-page">

            <a href="{{ route('landing') }}" class="auth-guest-logo" aria-label="NutriLife — inicio">
                <img src="{{ asset('images/brand/nutrilife-logo-light.png') }}" alt="NutriLife"
                     class="nl-logo nl-logo--on-light" width="640" height="804">
                <img src="{{ asset('images/brand/nutrilife-logo-dark.png') }}" alt="NutriLife"
                     class="nl-logo nl-logo--on-dark" width="640" height="804">
            </a>

            <div class="auth-guest-card">
                {{ $slot }}
            </div>

            <div class="auth-guest-footer">
                © {{ date('Y') }} NutriLife · Tu clínica de nutrición digital
            </div>

        </main>
    </body>
</html>
