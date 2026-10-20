@extends('adminlte::page')

@section('title', 'Nuevo plan alimenticio | NutriLife')

@section('content_header')
    <div class="consultations-header">
        <div>
            <span class="consultation-eyebrow">
                <i class="fas fa-leaf mr-1"></i>
                PLANES ALIMENTICIOS
            </span>
            <h1>Nuevo plan</h1>
            <p>Elige al paciente y define sus objetivos diarios.</p>
        </div>

        <a href="{{ route('diet-plans.index') }}" class="btn-consultation-primary">
            <i class="fas fa-arrow-left"></i>
            Regresar
        </a>
    </div>
@stop

@section('content')
    @include('diet-plans.partials.form', ['plan' => null])
@stop

@section('css')
    @include('partials.theme-styles', ['section' => 'admin'])
@stop

@section('js')
    @vite(['resources/js/pages/diet-plans.js'])
@stop
