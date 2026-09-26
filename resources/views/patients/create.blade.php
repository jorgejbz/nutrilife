@extends('adminlte::page')

@section('title', 'Nuevo paciente | NutriLife')


{{-- =========================================================
     CONTENT HEADER
     ========================================================= --}}
@section('content_header')

    <div class="patient-form-header">

        <div>
            <span class="header-eyebrow">
                <i class="fas fa-leaf mr-1"></i>
                NUTRILIFE
            </span>

            <h1>Nuevo paciente</h1>

            <p>Registra la información general del paciente.</p>
        </div>

        <a href="{{ route('pacientes.index') }}" class="btn-liquid-secondary">
            <i class="fas fa-arrow-left"></i>
            <span>Regresar</span>
        </a>

    </div>

@stop


{{-- =========================================================
     CONTENT
     ========================================================= --}}
@section('content')

    <div class="nutri-patient-form-page">

        {{-- Luces ambientales --}}
        <div class="ambient ambient-one"></div>
        <div class="ambient ambient-two"></div>
        <div class="ambient ambient-three"></div>

        <form method="POST" action="{{ route('pacientes.store') }}" id="patientForm">
            @csrf

            <div class="patient-form-layout">

                {{-- =========================================
                     FORMULARIO PRINCIPAL
                     ========================================= --}}
                @include('patients.partials.create-form')

                {{-- =========================================
                     PANEL DERECHO (vista previa)
                     ========================================= --}}
                @include('patients.partials.create-sidebar')

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



    @vite(['resources/js/pages/patients-create.js'])

@stop
