{{-- =========================================================
     DETALLE DEL DIÁLOGO DE CONFIRMACIÓN (contratar / cambiar paquete)
     forms.js lo copia dentro del diálogo (data-confirm-details).
     Todo sale de la lógica real:
       - vigencias: PlanCheckout::activate() cancela el paquete actual
         hoy y el nuevo dura PlanCheckout::periodEnd().
       - funciones: config('nutrilife.features') ($featurePlans).
       - límite de pacientes: subscription_plans.max_patients.
     Variables: $plan, $currentPlan, $subscription, $period,
     $overLimit, $patientCount, $featurePlans.
     ========================================================= --}}
@php
    $today = now();
    $newEnds = \App\Services\PlanCheckout::periodEnd($plan, $today);
    $limitLabel = fn ($p) => $p->max_patients ? 'Hasta '.$p->max_patients.' pacientes' : 'Pacientes sin límite';
    $periodLabel = fn ($p) => $p->billing_period === 'anual' ? 'año' : 'mes';

    $featureLabels = collect(config('nutrilife.features', []))->map(fn ($f) => $f['label'] ?? '');
    $lost = $currentPlan
        ? $featurePlans->filter(fn ($plans) => in_array($currentPlan->name, $plans, true) && ! in_array($plan->name, $plans, true))->keys()->map(fn ($k) => $featureLabels[$k])
        : collect();
    $gained = $currentPlan
        ? $featurePlans->filter(fn ($plans) => ! in_array($currentPlan->name, $plans, true) && in_array($plan->name, $plans, true))->keys()->map(fn ($k) => $featureLabels[$k])
        : collect();
@endphp

<template id="plan-confirm-{{ $plan->id }}">
    <div class="nl-plan-compare {{ $currentPlan ? '' : 'is-single' }}">
        @if($currentPlan)
            <div class="nl-plan-compare__card">
                <span class="nl-plan-compare__tag">Paquete actual</span>
                <strong class="nl-plan-compare__name">{{ $currentPlan->name }}</strong>
                <span class="nl-plan-compare__price">${{ number_format((float) $currentPlan->price, 2) }} <small>MXN / {{ $periodLabel($currentPlan) }}</small></span>
                <ul>
                    <li><i class="fas fa-users" aria-hidden="true"></i> {{ $limitLabel($currentPlan) }}</li>
                    <li class="is-ending"><i class="fas fa-calendar-times" aria-hidden="true"></i> Termina hoy, {{ $today->format('d/m/Y') }}</li>
                </ul>
            </div>

            <span class="nl-plan-compare__arrow" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
        @endif

        <div class="nl-plan-compare__card is-new">
            <span class="nl-plan-compare__tag">{{ $currentPlan ? 'Nuevo paquete' : 'Paquete' }}</span>
            <strong class="nl-plan-compare__name">{{ $plan->name }}</strong>
            <span class="nl-plan-compare__price">${{ number_format((float) $plan->price, 2) }} <small>MXN / {{ $period }}</small></span>
            <ul>
                <li><i class="fas fa-users" aria-hidden="true"></i> {{ $limitLabel($plan) }}</li>
                <li><i class="fas fa-calendar-check" aria-hidden="true"></i> Del {{ $today->format('d/m/Y') }} al {{ $newEnds->format('d/m/Y') }}</li>
            </ul>
        </div>
    </div>

    <ul class="nl-confirm-notes">
        @foreach($lost as $label)
            <li class="is-warning">
                <i class="fas fa-lock" aria-hidden="true"></i>
                <span>Dejarás de tener acceso a <strong>{{ $label }}</strong>. No se borra ninguna información.</span>
            </li>
        @endforeach

        @foreach($gained as $label)
            <li class="is-success">
                <i class="fas fa-unlock" aria-hidden="true"></i>
                <span>Tendrás acceso a <strong>{{ $label }}</strong>.</span>
            </li>
        @endforeach

        @if($overLimit)
            <li class="is-warning">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <span>Tienes {{ $patientCount }} pacientes; este paquete contempla hasta {{ $plan->max_patients }}.</span>
            </li>
        @endif

        <li class="is-info">
            <i class="fas fa-info-circle" aria-hidden="true"></i>
            <span>Pago simulado: no se hará ningún cargo. El cobro quedará registrado como «{{ config('nutrilife.checkout.payment_method', 'Simulado') }}» en tu historial.</span>
        </li>
    </ul>
</template>
