@extends('adminlte::page')

@section('title', 'Progreso | NutriLife')


{{-- =========================================================
     CONTENT HEADER
     ========================================================= --}}
@section('content_header')

    <div class="progress-header">

        <div>
            <span class="progress-eyebrow">
                <i class="fas fa-leaf mr-1"></i>
                NUTRILIFE
            </span>

            <h1>Progreso</h1>

            <p>Consulta la evolución de tus pacientes.</p>
        </div>

    </div>

@stop


{{-- =========================================================
     CONTENT
     ========================================================= --}}
@section('content')

    <div class="progress-page">

        {{-- =================================================
             MÉTRICAS
             ================================================= --}}
        @include('progress.partials.index-stats')


        {{-- =================================================
             PACIENTES
             ================================================= --}}
        @include('progress.partials.index-patients')

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


@stop
