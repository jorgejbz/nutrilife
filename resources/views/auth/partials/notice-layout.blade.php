{{-- =========================================================
     Pantalla de aviso de autenticación (una tarjeta centrada)
     Reutiliza public/css/login/login.css (ambientes, login-glass). La usan:
     register-thanks (después de registrarse) y account-pending
     (cuenta todavía no habilitada).
     Variables: $title, $slot (contenido), $icon
     ========================================================= --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }} | NutriLife</title>
    @include('partials.theme-styles', ['section' => 'login'])
</head>
<body class="auth-login">
<div class="login-page">
    <div class="ambient ambient-one"></div>
    <div class="ambient ambient-two"></div>
    <div class="ambient ambient-three"></div>

    <main class="auth-notice">
        <a href="{{ url('/') }}" class="auth-notice-logo">
            <img src="{{ asset('images/brand/nutrilife-wordmark-light.png') }}" alt="NutriLife"
                 class="nl-logo nl-logo--on-light" width="600" height="186">
            <img src="{{ asset('images/brand/nutrilife-wordmark-dark.png') }}" alt="NutriLife"
                 class="nl-logo nl-logo--on-dark" width="600" height="186">
        </a>

        <div class="login-glass auth-notice-card">
            <div class="login-glass-shine"></div>
            {!! $content !!}
        </div>

        <p class="auth-notice-foot">© {{ date('Y') }} NutriLife · Gestión para nutriólogos</p>
    </main>
</div>
</body>
</html>
