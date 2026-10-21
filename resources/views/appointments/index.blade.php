@extends('adminlte::page')

@section('title', 'Citas | NutriLife')


{{-- =========================================================
     CONTENT HEADER
     ========================================================= --}}
@section('content_header')

    <div class="appointments-header">

        <div>
            <span class="header-eyebrow">
                <i class="fas fa-leaf me-1"></i>
                NUTRILIFE
            </span>

            <h1>Calendario</h1>

            <p>Organiza y consulta las citas de tus pacientes.</p>
        </div>

        <a href="{{ route('citas.create') }}" class="btn-liquid-primary">
            <i class="fas fa-plus"></i>
            <span>Nueva cita</span>
        </a>

    </div>

@stop


{{-- =========================================================
     CONTENT
     ========================================================= --}}
@section('content')

    @php
        /*
        |--------------------------------------------------------------------------
        | Datos para el calendario (se serializan a JSON en @section('js'))
        |--------------------------------------------------------------------------
        */
        $calendarAppointments = $appointments->map(function ($appointment) {
            return [
                'id' => $appointment->id,
                'date' => $appointment->appointment_at->format('Y-m-d H:i:s'),
                'patient' => trim(
                    ($appointment->patient->first_name ?? '') . ' ' .
                    ($appointment->patient->last_name ?? '')
                ),
                'reason' => $appointment->reason ?? 'Consulta nutricional',
                'status' => $appointment->status ?? 'Pendiente',
                // La edición de citas no está implementada (el método edit del
                // controlador estaba vacío), por eso ya no se envía edit_url.
                'cancel_url' => route('citas.cancel', $appointment),
                'delete_url' => route('citas.destroy', $appointment),
            ];
        })->values();

        $upcomingAppointments = $appointments
            ->filter(function ($appointment) {
                return $appointment->appointment_at >= now()->startOfDay();
            })
            ->sortBy('appointment_at')
            ->take(5);
    @endphp

    <div class="nutri-calendar-page">

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

        @if(session('error'))
            <div class="glass-alert glass-alert--error" role="alert">
                <div class="glass-alert-icon">
                    <i class="fas fa-exclamation"></i>
                </div>

                <div>
                    <strong>No se pudo completar</strong>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif


        {{-- =================================================
             HERO
             ================================================= --}}
        @include('appointments.partials.index-hero')


        {{-- =================================================
             MÉTRICAS
             ================================================= --}}
        @include('appointments.partials.index-stats')


        {{-- =================================================
             CALENDARIO
             ================================================= --}}
        @include('appointments.partials.index-calendar')

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

    {{-- Solo pasamos los datos del servidor a JS; toda la lógica
         del calendario vive en resources/js/pages/appointments-index.js --}}
    <script>
        window.appointmentsIndexData = {
            appointments: @json($calendarAppointments),
            csrfToken: @json(csrf_token()),
        };
    </script>

    @vite(['resources/js/pages/appointments-index.js'])

@stop
