{{-- =========================================================
     TIPO DE CUENTA DEL NUTRIÓLOGO (barra superior del panel)
     Visible en todas las páginas:
       - Prueba gratuita · X días   (en_prueba; ámbar los últimos 2 días)
       - Prueba terminada · Elegir plan
       - Plan Esencial / Pro / Consultorio   (suscripción activa)
       - Plan cancelado / Suscripción vencida
       - Administrador   (role_id = 2)
     Datos: Subscription::accountTypeFor y ::panelStatusFor.
     ========================================================= --}}
@php
    $nlAccount = \App\Models\Subscription::accountTypeFor(auth()->user());
    $nlTrial = \App\Models\Subscription::panelStatusFor(auth()->user());
@endphp

@if ($nlAccount)
    <li class="nav-item d-flex align-items-center">

        @switch($nlAccount['type'])

            @case('trial')
                <a href="{{ route('profile.edit') }}"
                   class="nl-trial-badge {{ ($nlTrial['days'] ?? 7) <= 2 ? 'is-ending' : '' }}"
                   title="Tu prueba termina el {{ $nlTrial['ends_at']->format('d/m/Y') }}">
                    <i class="fas fa-seedling" aria-hidden="true"></i>
                    <span>Prueba gratuita</span>
                    <strong>
                        @if ($nlTrial['days'] === 0)
                            Último día
                        @elseif ($nlTrial['days'] === 1)
                            1 día
                        @else
                            {{ $nlTrial['days'] }} días
                        @endif
                    </strong>
                </a>
                @break

            @case('active')
                <a href="{{ route('profile.edit') }}"
                   class="nl-trial-badge is-plan is-plan-{{ \Illuminate\Support\Str::slug($nlAccount['plan'] ?? '') }}"
                   title="Ver mi suscripción">
                    <i class="fas fa-crown" aria-hidden="true"></i>
                    <span>Plan</span>
                    <strong>{{ $nlAccount['plan'] }}</strong>
                </a>
                @break

            @case('admin')
                <span class="nl-trial-badge is-admin">
                    <i class="fas fa-user-shield" aria-hidden="true"></i>
                    <strong>Administrador</strong>
                </span>
                @break

            @default
                {{-- expired / cancelled --}}
                <a href="{{ route('profile.edit') }}" class="nl-trial-badge is-expired" title="Ver mi suscripción">
                    <i class="fas fa-hourglass-end" aria-hidden="true"></i>
                    <span>{{ $nlAccount['label'] }}</span>
                    <strong>Elegir plan</strong>
                </a>

        @endswitch

    </li>
@endif
