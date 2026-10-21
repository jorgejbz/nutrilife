@extends('adminlte::page')

@section('title', 'Nueva cita | NutriLife')


{{-- =========================================================
     CONTENT HEADER
     ========================================================= --}}
@section('content_header')

    <div class="appointment-page-header">

        <div>
            <span class="header-eyebrow">
                <i class="fas fa-leaf me-1"></i>
                NUTRILIFE · AGENDA
            </span>

            <h1>Nueva cita</h1>

            <p>Programa una consulta con uno de tus pacientes.</p>
        </div>

        <a href="{{ route('citas.index') }}" class="btn-liquid-secondary">
            <i class="fas fa-arrow-left"></i>
            Regresar
        </a>

    </div>

@stop


{{-- =========================================================
     CONTENT
     ========================================================= --}}
@section('content')

    <div class="appointment-create-page">

        {{-- Luces ambientales --}}
        <div class="ambient ambient-one"></div>
        <div class="ambient ambient-two"></div>
        <div class="ambient ambient-three"></div>

        <form method="POST" action="{{ route('citas.store') }}" id="appointmentForm">
            @csrf

            <div class="appointment-layout">

                {{-- =========================================
                     FORMULARIO PRINCIPAL
                     ========================================= --}}
                @include('appointments.partials.create-form')

                {{-- =========================================
                     PANEL DERECHO
                     ========================================= --}}
                @include('appointments.partials.create-sidebar')

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



    @vite(['resources/js/pages/appointments-create.js'])

@stop
