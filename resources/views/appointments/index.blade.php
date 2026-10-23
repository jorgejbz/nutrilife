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

            <p>{{ $isClinic ? 'Citas de todo el equipo de tu clínica, con el nutriólogo de cada una.' : 'Organiza y consulta las citas de tus pacientes.' }}</p>
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
        $calendarAppointments = $appointments->map(function ($appointment) use ($isClinic) {
            $own = $appointment->user_id === auth()->id();

            return [
                'id' => $appointment->id,
                // Clínica: con qué nutriólogo es la cita (appointments.user_id)
                'nutritionist_id' => $appointment->user_id,
                'nutritionist' => $isClinic ? $appointment->user?->name : null,
                'is_own' => $own,
                'date' => $appointment->appointment_at->format('Y-m-d H:i:s'),
                'patient' => trim(
                    ($appointment->patient->first_name ?? '') . ' ' .
                    ($appointment->patient->last_name ?? '')
                ),
                'reason' => $appointment->reason ?? 'Consulta nutricional',
                'status' => $appointment->status ?? 'Pendiente',
                'notes' => $appointment->notes,
                // El expediente se abre solo si el paciente sigue vigente
                // (patients usa borrado lógico).
                'patient_url' => $appointment->patient && ! $appointment->patient->trashed()
                    ? route('pacientes.show', $appointment->patient)
                    : null,
                // Mismo flujo de /consultas/create con la cita preseleccionada;
                // solo para citas abiertas (las que acepta ConsultationController).
                'consultation_url' => $own && in_array($appointment->status, ['Pendiente', 'Confirmada'], true)
                    && $appointment->patient && ! $appointment->patient->trashed()
                    ? route('consultas.create', ['cita' => $appointment->id])
                    : null,
                // La edición de citas no está implementada (el método edit del
                // controlador estaba vacío), por eso ya no se envía edit_url.
                // Solo quien agendó la cita la confirma, cancela o elimina
                // (AppointmentController).
                'confirm_url' => $own && $appointment->status === 'Pendiente'
                    ? route('citas.confirm', $appointment)
                    : null,
                'cancel_url' => $own ? route('citas.cancel', $appointment) : null,
                'delete_url' => $own ? route('citas.destroy', $appointment) : null,
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

    {{-- Estilos del panel: tokens + public/css/admin/* + temas + responsive (config/stylesheets.php),
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
            // Fechas en la zona horaria de NutriLife (config/app.php)
            today: @json($today),
            selectedDay: @json($selectedDay),
            createUrl: @json(route('citas.create')),
            csrfToken: @json(csrf_token()),
        };
    </script>

    @vite(['resources/js/pages/appointments-index.js'])

@stop
