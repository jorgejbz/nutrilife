@extends('adminlte::page')

@section('title', 'Nuevo plan alimenticio | NutriLife')

{{-- =========================================================
     ENCABEZADO
     ========================================================= --}}
@section('content_header')
<div class="consultations-header dp">
    <div>
        <span class="consultation-eyebrow">
            <i class="fas fa-leaf me-1"></i>
            NUTRILIFE
        </span>

        <h1>Nuevo plan alimenticio</h1>

        <p>
            Diseña un plan nutricional personalizado
            y establece los objetivos de tu paciente.
        </p>
    </div>

    <a href="{{ route('diet-plans.index') }}" class="dp-back-button">
        <i class="fas fa-arrow-left"></i>
        Volver a planes
    </a>
</div>
@stop

{{-- =========================================================
     CONTENIDO
     ========================================================= --}}
@section('content')
    @include('diet-plans.partials.form', ['plan' => null])
@stop

@section('css')
    @include('diet-plans.partials.styles')
@stop

@section('js')
    @vite(['resources/js/pages/diet-plans.js'])
@stop
