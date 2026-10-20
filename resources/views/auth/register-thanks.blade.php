{{-- Después de registrarse (RegisteredUserController@thanks).
     La cuenta queda "pendiente" y se habilita sola (AccountActivation). --}}
@php
    // Primer nombre sin títulos (Dr., Dra., Lic., Mtra., L.N.…)
    $firstName = $name
        ? collect(preg_split('/\s+/', trim($name)))
            ->reject(fn ($part) => preg_match('/^(dr|dra|lic|licda|mtro|mtra|l\.?n|nut|nutr)\.?$/i', $part))
            ->first()
        : null;
@endphp

@include('auth.partials.notice-layout', [
    'pageTitle' => 'Gracias por registrarte',
    'content' => view('auth.partials.notice-content', [
        'icon' => 'fas fa-seedling',
        'eyebrow' => 'REGISTRO RECIBIDO',
        'title' => '¡Gracias por registrarte en NutriLife'.($firstName ? ', '.$firstName : '').'!',
        'lines' => [
            'Hemos recibido tu información. Estamos preparando tu cuenta para que puedas comenzar a utilizar la plataforma.',
            $delayMinutes > 0
                ? 'Tu acceso quedará habilitado en aproximadamente '.$delayMinutes.' '.($delayMinutes === 1 ? 'minuto' : 'minutos').'. Después podrás iniciar sesión con tu correo'.($email ? ' '.$email : '').' y tu contraseña.'
                : 'Tu acceso ya está habilitado: puedes iniciar sesión con tu correo y contraseña.',
        ],
        'steps' => [
            ['icon' => 'far fa-id-card', 'text' => 'Guardamos tu cédula profesional y tu número de título.'],
            ['icon' => 'far fa-clock', 'text' => 'Preparamos tu cuenta.'],
            ['icon' => 'fas fa-seedling', 'text' => 'Al entrar por primera vez empieza tu prueba gratuita de '.config('nutrilife.trial_days').' días.'],
        ],
        'action' => ['url' => route('login'), 'label' => 'Ir a iniciar sesión'],
        'note' => 'La cédula y el título aún no se validan con la SEP: por ahora solo se registran.',
    ])->render(),
])
