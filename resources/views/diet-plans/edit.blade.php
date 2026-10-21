@extends('adminlte::page')

@section('title', 'Editar plan alimenticio | NutriLife')

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

        <h1>Editar plan alimenticio</h1>

        <p>{{ $plan->name }}</p>
    </div>

    <a href="{{ route('diet-plans.show', $plan) }}" class="dp-back-button">
        <i class="fas fa-arrow-left"></i>
        Volver al plan
    </a>
</div>
@stop

{{-- =========================================================
     CONTENIDO
     ========================================================= --}}
@section('content')
    @include('diet-plans.partials.form', ['plan' => $plan])
@stop

@section('css')
    @include('diet-plans.partials.styles')
@stop

@section('js')
    @vite(['resources/js/pages/diet-plans.js'])
@stop
