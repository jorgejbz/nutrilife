@extends('adminlte::page')

@section('title', 'Nueva evaluación | NutriLife')


{{-- =========================================================
     CONTENT HEADER
     ========================================================= --}}
@section('content_header')

    <div class="evaluation-page-header">

        <div>
            <span class="header-eyebrow">
                <i class="fas fa-leaf me-1"></i>
                NUTRILIFE · SEGUIMIENTO
            </span>

            <h1>Nueva evaluación</h1>

            <p>
                {{ $paciente->first_name }}
                {{ $paciente->last_name }}
            </p>
        </div>

        <a href="{{ route('pacientes.show', $paciente) }}" class="btn-liquid-secondary">
            <i class="fas fa-arrow-left"></i>
            Regresar
        </a>

    </div>

@stop


{{-- =========================================================
     CONTENT
     ========================================================= --}}
@section('content')

    @php
        // min/max: mismos rangos que valida EvaluationController@store
        $measurements = [
            'waist' => ['label' => 'Cintura', 'icon' => 'fa-ruler-horizontal', 'min' => 20, 'max' => 300],
            'hip' => ['label' => 'Cadera', 'icon' => 'fa-ruler', 'min' => 20, 'max' => 300],
            'chest' => ['label' => 'Pecho', 'icon' => 'fa-expand', 'min' => 20, 'max' => 300],
            'arm' => ['label' => 'Brazo', 'icon' => 'fa-dumbbell', 'min' => 10, 'max' => 100],
            'thigh' => ['label' => 'Muslo', 'icon' => 'fa-ruler-combined', 'min' => 10, 'max' => 150],
        ];
    @endphp

    <div class="evaluation-page">

        {{-- Luces ambientales --}}
        <div class="ambient ambient-one"></div>
        <div class="ambient ambient-two"></div>
        <div class="ambient ambient-three"></div>

        <form method="POST" action="{{ route('evaluaciones.store', $paciente) }}" id="evaluationForm">
            @csrf

            <div class="evaluation-layout">

                {{-- =========================================
                     FORMULARIO
                     ========================================= --}}
                @include('evaluations.partials.create-form')

                {{-- =========================================
                     PANEL DERECHO
                     ========================================= --}}
                @include('evaluations.partials.create-sidebar')

            </div>

        </form>

    </div>

@stop


{{-- =========================================================
     ESTILOS
     ========================================================= --}}
@section('css')

    {{-- Estilos del panel: tokens + admin.css + admin-light/dark.css,
         anti-parpadeo del tema, tipografía e iconos --}}
    @include('partials.theme-styles', ['section' => 'admin'])

@stop


{{-- =========================================================
     SCRIPTS
     ========================================================= --}}
@section('js')



    @vite(['resources/js/pages/evaluations-create.js'])

@stop
