{{-- =========================================================
     FUNCIÓN NO INCLUIDA EN EL PLAN (HTTP 403)
     La muestra App\Http\Middleware\EnsureFeature cuando el plan
     del nutriólogo no incluye una sección (p. ej. Planes
     alimenticios en el plan Esencial o con la prueba vencida).
     ========================================================= --}}
@extends('adminlte::page')

@section('title', $featureLabel.' | NutriLife')

@section('content_header')
    <div class="consultations-header">
        <div>
            <span class="consultation-eyebrow">
                <i class="fas fa-leaf me-1"></i>
                NUTRILIFE
            </span>
            <h1>{{ $featureLabel }}</h1>
            <p>Esta sección no está incluida en tu plan actual.</p>
        </div>
    </div>
@stop

@section('content')
    <div class="consultations-page diet-page">
        <section class="consultations-card feature-locked" aria-labelledby="lockedTitle">
            <span class="feature-locked-icon" aria-hidden="true">
                <i class="fas fa-lock"></i>
            </span>

            <h2 id="lockedTitle">Disponible en {{ implode(' y ', $plans) }}</h2>

            @if($isAdmin)
                <p>{{ $featureLabel }} es una herramienta para las cuentas de nutriólogo.</p>
            @else
                <p>
                    @if($accountType)
                        Estado de tu cuenta: <strong>{{ $accountType['label'] }}</strong>.
                    @endif
                    Para diseñar planes alimenticios para tus pacientes necesitas el plan
                    {{ implode(' o ', $plans) }} con la suscripción vigente.
                </p>

                @if(($accountType['type'] ?? null) === 'expired' && ($accountType['plan'] ?? null) === null && $includesTrial)
                    <p class="diet-muted">Tu prueba gratuita la incluía; para seguir usándola elige Pro o Consultorio.</p>
                @endif

                <div class="feature-locked-actions">
                    <a href="{{ route('perfil') }}" class="btn-consultation-primary">
                        <i class="fas fa-user-circle" aria-hidden="true"></i>
                        Ver mi plan
                    </a>
                    <a href="{{ route('dashboard.index') }}" class="diet-btn diet-btn--ghost">
                        Ir al dashboard
                    </a>
                </div>
            @endif
        </section>
    </div>
@stop

@section('css')
    @include('partials.theme-styles', ['section' => 'admin'])
@stop
