@extends('adminlte::page')

@section('title', 'Pacientes | NutriLife')


{{-- =========================================================
     CONTENT HEADER
     ========================================================= --}}
@section('content_header')

    <div class="patients-header">

        <div>
            <span class="header-eyebrow">
                <i class="fas fa-leaf me-1"></i>
                NUTRILIFE
            </span>

            <h1>Pacientes</h1>

            <p>
                Administra y consulta la información de tus pacientes.
            </p>
        </div>

        <a href="{{ route('pacientes.create') }}"
           class="btn-liquid-primary">

            <i class="fas fa-plus"></i>

            <span>Nuevo paciente</span>

        </a>

    </div>

@stop


{{-- =========================================================
     CONTENT
     ========================================================= --}}
@section('content')

    <div class="nutri-patients-page">

        {{-- Decoración de fondo --}}
        <div class="ambient ambient-one"></div>
        <div class="ambient ambient-two"></div>
        <div class="ambient ambient-three"></div>


        @if(session('success'))
            <div class="glass-alert">
                <div class="glass-alert-icon">
                    <i class="fas fa-check"></i>
                </div>

                <div>
                    <strong>¡Listo!</strong>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif


        {{-- =================================================
             HERO
             ================================================= --}}
        @include('patients.partials.hero')


        {{-- =================================================
             MÉTRICAS
             ================================================= --}}
        @include('patients.partials.stats')


        {{-- =================================================
             BUSCADOR
             ================================================= --}}
        @include('patients.partials.toolbar')


        {{-- =================================================
             LISTADO
             ================================================= --}}
        @include('patients.partials.list')

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



    @vite(['resources/js/pages/patients-index.js'])

@stop
