@extends('adminlte::page')

@section('title', 'Ficha del paciente | NutriLife')


{{-- =========================================================
     CONTENT HEADER
     ========================================================= --}}
@section('content_header')

    <div class="patient-show-header">

        <div>
            <span class="header-eyebrow">
                <i class="fas fa-leaf me-1"></i>
                EXPEDIENTE · NUTRILIFE
            </span>

            <h1>
                {{ $paciente->first_name }}
                {{ $paciente->last_name }}
            </h1>

            <p>Perfil, evaluaciones y progreso nutricional.</p>
        </div>

        <div class="header-actions">
            <a href="{{ $canTreat ? route('pacientes.index') : route('clinic.patients.index') }}" class="btn-liquid-secondary">
                <i class="fas fa-arrow-left"></i>
                Regresar
            </a>

            <a href="{{ route('pacientes.edit', $paciente) }}" class="btn-liquid-primary">
                <i class="far fa-edit"></i>
                Editar paciente
            </a>
        </div>

    </div>

@stop


{{-- =========================================================
     CONTENT
     ========================================================= --}}
@section('content')

    <div class="patient-show-page">

        {{-- Luces --}}
        <div class="ambient ambient-one"></div>
        <div class="ambient ambient-two"></div>
        <div class="ambient ambient-three"></div>

        @if(session('success'))
            <div class="glass-alert">
                <div class="glass-alert-icon">
                    <i class="fas fa-check"></i>
                </div>

                <div>
                    <strong>Actualización completada</strong>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif


        {{-- =================================================
             HERO
             ================================================= --}}
        @include('patients.partials.show-hero')

        @unless ($canTreat)
            <div class="glass-alert glass-alert--info" role="note">
                <div class="glass-alert-icon">
                    <i class="fas fa-user-doctor"></i>
                </div>
                <div>
                    <strong>Paciente de {{ $responsible?->name }}</strong>
                    <span>Lo consultas como cuenta principal de la clínica. Evaluaciones, citas y planes alimenticios los registra su nutriólogo responsable; puedes cambiarlo desde «Editar paciente».</span>
                </div>
            </div>
        @endunless


        {{-- =================================================
             GRID SUPERIOR
             ================================================= --}}
        @include('patients.partials.show-info-grid')


        {{-- =================================================
             HISTORIAL CLÍNICO (patologías y alergias)
             ================================================= --}}
        @include('patients.partials.show-clinical')


        {{-- =================================================
             PLANES ALIMENTICIOS (solo Pro, Consultorio y prueba)
             ================================================= --}}
        @can('diet-plans')
            @include('patients.partials.show-diet-plans')
        @endcan


        {{-- =================================================
             ÚLTIMA EVALUACIÓN
             ================================================= --}}
        @include('patients.partials.show-latest-evaluation')


        {{-- =================================================
             PROGRESO
             ================================================= --}}
        @include('patients.partials.show-progress')


        {{-- =================================================
             GRÁFICA
             ================================================= --}}
        @include('patients.partials.show-chart')


        {{-- =================================================
             HISTORIAL
             ================================================= --}}
        @include('patients.partials.show-history')

    </div>

@stop


{{-- =========================================================
     ESTILOS
     ========================================================= --}}
@section('css')

    {{-- Estilos del panel: tokens + public/css/admin/* + temas + responsive (config/stylesheets.php),
         anti-parpadeo del tema, tipografía e iconos --}}
    @include('partials.theme-styles', ['section' => 'admin'])

@stop


{{-- =========================================================
     SCRIPTS
     ========================================================= --}}
@section('js')



    @if($showWeightChart)

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        {{-- Solo pasamos los datos del servidor a JS; la gráfica
             vive en resources/js/pages/patients-show.js --}}
        <script>
            window.patientShowData = {
                weightLabels: @json($weightLabels),
                weightData: @json($weightData),
            };
        </script>

        @vite(['resources/js/pages/patients-show.js'])

    @endif

@stop
