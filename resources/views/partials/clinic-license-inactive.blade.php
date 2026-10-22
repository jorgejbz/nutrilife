{{-- =========================================================
     LICENCIA DE LA CLÍNICA NO VIGENTE (HTTP 402)
     La muestra EnsureTrialSubscription a un subperfil de nutriólogo
     cuando el plan Consultorio de su clínica venció o fue cambiado.
     Sus pacientes y planes se conservan; solo se pausa el acceso.
     ========================================================= --}}
@extends('adminlte::page')

@section('title', 'Licencia de la clínica | NutriLife')

@section('content_header')
    <div class="patients-header">
        <div>
            <span class="header-eyebrow">
                <i class="fas fa-leaf me-1"></i>
                NUTRILIFE · CLÍNICA
            </span>
            <h1>Licencia de la clínica</h1>
            <p>Tu acceso depende del plan Consultorio de {{ $clinicName ?? 'tu clínica' }}.</p>
        </div>
    </div>
@stop

@section('content')
    <div class="nl-profile">
        <section class="nl-profile-card">
            <div class="nl-alert nl-alert--info">
                <i class="fas fa-hospital" aria-hidden="true"></i>
                <div>{{ $message }}</div>
            </div>
            <p class="nl-muted nl-mt-14">
                Tus pacientes, citas y planes alimenticios se conservan y volverán a estar
                disponibles en cuanto la clínica renueve su plan.
            </p>
            <div class="nl-page-actions nl-mt-18">
                <a href="{{ route('perfil') }}" class="nl-btn nl-btn--ghost">
                    <i class="fas fa-user-circle" aria-hidden="true"></i>
                    Ver mi perfil
                </a>
            </div>
        </section>
    </div>
@stop

@section('css')
    @include('partials.theme-styles', ['section' => 'admin'])
@stop
