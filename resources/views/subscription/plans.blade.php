@extends('adminlte::page')

@section('title', 'Elige tu paquete | NutriLife')

{{-- =========================================================
     CONTRATACIÓN DE PAQUETES (SubscriptionController@index)
     Datos reales: subscription_plans (nombre, precio, periodo,
     max_patients) + config/nutrilife.php (plan_features).
     ========================================================= --}}

@section('content_header')
    <div class="consultations-header">
        <div>
            <span class="consultation-eyebrow">
                <i class="fas fa-leaf mr-1"></i>
                PAQUETES NUTRILIFE
            </span>
            <h1>{{ $hasAccess ? 'Cambia de paquete' : 'Elige tu paquete' }}</h1>
            <p>Precios en pesos mexicanos. Puedes cambiar de paquete cuando lo necesites.</p>
        </div>

        @if($hasAccess)
            <a href="{{ route('dashboard.index') }}" class="diet-btn diet-btn--ghost">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                Volver al panel
            </a>
        @endif
    </div>
@stop

@section('content')
<div class="consultations-page plans-page">

    {{-- Motivo del bloqueo --}}
    @unless($hasAccess)
        <section class="plans-lock" role="alert">
            <span class="plans-lock-icon" aria-hidden="true">
                <i class="fas {{ $trialEnded ? 'fa-hourglass-end' : 'fa-calendar-times' }}"></i>
            </span>
            <div>
                <h2>{{ $trialEnded ? 'Tu periodo de prueba terminó' : 'Tu suscripción terminó' }}</h2>
                <p>
                    @if($trialEnded)
                        Los {{ config('nutrilife.trial_days') }} días de prueba gratuita
                        @if($subscription?->isTrial() || $subscription?->status === 'vencida')
                            terminaron el {{ $subscription->trialEndsAt()->format('d/m/Y \a \l\a\s H:i') }}.
                        @endif
                    @else
                        Tu paquete {{ $subscription?->plan?->name }} venció el {{ $subscription?->current_period_ends_at?->format('d/m/Y') }}.
                    @endif
                    Elige un paquete para seguir usando NutriLife.
                    @if($patientCount > 0)
                        Tus {{ $patientCount }} {{ $patientCount === 1 ? 'paciente' : 'pacientes' }} y toda tu información siguen guardados.
                    @else
                        Tu información sigue guardada.
                    @endif
                </p>
            </div>
        </section>
    @endunless

    @if($errors->any())
        <div class="diet-form-alert" role="alert">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <div>
                <strong>No se pudo completar la contratación</strong>
                <span>{{ $errors->first() }}</span>
            </div>
        </div>
    @endif

    @if($plans->isEmpty())
        <div class="consultations-card empty-consultations">
            <i class="fas fa-box-open"></i>
            <h5>No hay paquetes disponibles</h5>
            <p>Todavía no hay paquetes activos en la tabla <code>subscription_plans</code>.</p>
        </div>
    @else
        <div class="plans-grid">
            @foreach($plans as $plan)
                @php
                    $isCurrent = $currentPlanId === $plan->id;
                    $features = $planFeatures[$plan->name] ?? [];
                    $overLimit = $plan->max_patients !== null && $patientCount > $plan->max_patients;
                    $period = $plan->billing_period === 'anual' ? 'año' : 'mes';
                @endphp

                <article class="plans-card {{ $isCurrent ? 'is-current' : '' }}" aria-labelledby="plan-{{ $plan->id }}">
                    @if($isCurrent)
                        <span class="plans-current"><i class="fas fa-check-circle" aria-hidden="true"></i> Tu paquete actual</span>
                    @endif

                    <h2 id="plan-{{ $plan->id }}">{{ $plan->name }}</h2>

                    <div class="plans-price">
                        <strong>${{ number_format((float) $plan->price, 0) }}</strong>
                        <span>MXN / {{ $period }}</span>
                    </div>

                    <p class="plans-limit">
                        <i class="fas fa-users" aria-hidden="true"></i>
                        {{ $plan->max_patients ? 'Hasta '.$plan->max_patients.' pacientes' : 'Pacientes sin límite' }}
                    </p>

                    @if(count($features))
                        <ul class="plans-features">
                            @foreach($features as $feature)
                                <li class="{{ $feature['available'] ? '' : 'is-soon' }}">
                                    <i class="fas {{ $feature['available'] ? 'fa-check' : 'fa-clock' }}" aria-hidden="true"></i>
                                    <span>{{ $feature['label'] }}</span>
                                    @unless($feature['available'])
                                        <small>Próximamente</small>
                                    @endunless
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if($overLimit)
                        <p class="plans-warning">
                            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                            Tienes {{ $patientCount }} pacientes; este paquete contempla hasta {{ $plan->max_patients }}.
                        </p>
                    @endif

                    <div class="plans-action">
                        @if($isCurrent)
                            <button type="button" class="diet-btn diet-btn--ghost" disabled>
                                Activo hasta el {{ $subscription->current_period_ends_at->format('d/m/Y') }}
                            </button>
                        @elseif(! $checkoutEnabled)
                            <button type="button" class="diet-btn diet-btn--ghost" disabled>No disponible en línea</button>
                        @else
                            <form method="POST"
                                  action="{{ route('subscription.checkout', $plan) }}"
                                  data-confirm="¿Contratar el paquete {{ $plan->name }} por ${{ number_format((float) $plan->price, 2) }} MXN al {{ $period }}?{{ $currentPlanId ? ' Tu paquete actual se cancelará hoy.' : '' }}&#10;&#10;Es un pago simulado: no se hará ningún cargo.">
                                @csrf
                                <button type="submit" class="diet-btn diet-btn--primary" data-loading-text="Activando…">
                                    {{ $currentPlanId ? 'Cambiar a '.$plan->name : 'Elegir '.$plan->name }}
                                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <p class="plans-note">
            <i class="fas fa-info-circle" aria-hidden="true"></i>
            @if($checkoutEnabled)
                Por ahora la contratación es una <strong>simulación</strong>: el paquete se activa de inmediato y el
                cobro queda registrado como «Simulado» en tu historial, sin ningún cargo real.
            @else
                La contratación en línea no está disponible por ahora.
            @endif
            Las funciones marcadas como «Próximamente» todavía no existen en el sistema.
        </p>
    @endif
</div>
@stop

@section('css')
    @include('partials.theme-styles', ['section' => 'admin'])
@stop
