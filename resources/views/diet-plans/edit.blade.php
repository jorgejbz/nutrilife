@extends('adminlte::page')

@section('title', 'Editar plan alimenticio | NutriLife')

@section('content_header')
    <div class="consultations-header">
        <div>
            <span class="consultation-eyebrow">
                <i class="fas fa-leaf mr-1"></i>
                PLANES ALIMENTICIOS
            </span>
            <h1>Editar plan</h1>
            <p>{{ $plan->name }}</p>
        </div>

        <a href="{{ route('diet-plans.show', $plan) }}" class="btn-consultation-primary">
            <i class="fas fa-arrow-left"></i>
            Regresar al plan
        </a>
    </div>
@stop

@section('content')
    @include('diet-plans.partials.form', ['plan' => $plan])
@stop

@section('css')
    @include('partials.theme-styles', ['section' => 'admin'])
@stop

@section('js')
    @vite(['resources/js/pages/diet-plans.js'])
@stop
