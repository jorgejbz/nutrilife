{{-- =========================================================
     INDICADOR DE PRUEBA GRATUITA (barra superior del panel)
     Se muestra en todas las páginas mientras el nutriólogo está
     en su prueba de 7 días, y cuando ésta termina sin haber
     contratado un plan. Desaparece cuando la suscripción pasa a
     "activa" (compró el sistema).
     Cálculo: Subscription::panelStatusFor (mismo que el dashboard).
     ========================================================= --}}
@php
    $nlTrial = \App\Models\Subscription::panelStatusFor(auth()->user());
@endphp

@if ($nlTrial)
    <li class="nav-item d-flex align-items-center">
        <a
            href="{{ route('subscription.plans') }}"
            class="nl-trial-badge {{ $nlTrial['state'] === 'expired' ? 'is-expired' : '' }} {{ $nlTrial['state'] === 'trial' && $nlTrial['days'] <= 2 ? 'is-ending' : '' }}"
            title="{{ $nlTrial['state'] === 'trial' ? 'Tu prueba termina el '.$nlTrial['ends_at']->format('d/m/Y').'. Ver planes' : 'Ver planes' }}"
        >
            <i class="fas {{ $nlTrial['state'] === 'expired' ? 'fa-hourglass-end' : 'fa-seedling' }}" aria-hidden="true"></i>

            @if ($nlTrial['state'] === 'expired')
                <span>Prueba terminada</span>
                <strong>Elegir plan</strong>
            @else
                <span class="nl-trial-badge-label">Prueba gratuita</span>
                <strong>
                    @if ($nlTrial['days'] === 0)
                        Último día
                    @elseif ($nlTrial['days'] === 1)
                        1 día
                    @else
                        {{ $nlTrial['days'] }} días
                    @endif
                </strong>
            @endif
        </a>
    </li>
@endif
