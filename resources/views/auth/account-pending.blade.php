{{-- Cuenta registrada que todavía no se habilita
     (AuthenticatedSessionController@pending). --}}
@php
    $when = $activatesAt
        ? ($activatesAt->isToday() ? 'hoy a las '.$activatesAt->format('H:i') : 'el '.$activatesAt->format('d/m/Y \a \l\a\s H:i'))
        : null;
@endphp

@include('auth.partials.notice-layout', [
    'pageTitle' => 'Cuenta en preparación',
    'content' => view('auth.partials.notice-content', [
        'icon' => 'far fa-clock',
        'eyebrow' => 'CUENTA EN PREPARACIÓN',
        'title' => 'Tu cuenta todavía no está habilitada',
        'lines' => [
            'Estamos terminando de preparar tu acceso a NutriLife.',
            $when
                ? 'Podrás iniciar sesión '.$when.' (hora del centro de México).'
                : 'Intenta iniciar sesión de nuevo en unos minutos.',
        ],
        'steps' => [],
        'action' => ['url' => route('login'), 'label' => 'Volver a iniciar sesión'],
        'note' => null,
    ])->render(),
])
