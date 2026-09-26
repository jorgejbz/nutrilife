@extends('adminlte::page')


@section('title', 'Dashboard | NutriLife')


{{-- =========================================================
     CONTENT HEADER
     ========================================================= --}}
@section('content_header')

    @include('dashboard.partials.header')

@stop


{{-- =========================================================
     DASHBOARD CONTENT
     ========================================================= --}}
@section('content')

    @php
        /*
        |--------------------------------------------------------------------------
        | Valores predeterminados
        |--------------------------------------------------------------------------
        | Se mantienen temporalmente aquí para no alterar la lógica actual
        | del dashboard. Más adelante podemos mover esta lógica al
        | DashboardController.
        */

        $totalPatients = $totalPatients ?? 0;
        $activePatients = $activePatients ?? 0;

        $inactivePatients = max($totalPatients - $activePatients, 0);

        $activePercentage = $totalPatients > 0
            ? round(($activePatients / $totalPatients) * 100)
            : 0;

        $todayAppointmentsCount = $todayAppointmentsCount ?? 0;
        $monthAppointments = $monthAppointments ?? 0;
        $monthIncome = $monthIncome ?? 0;
        $completedAppointments = $completedAppointments ?? 0;

        /*
        |--------------------------------------------------------------------------
        | Datos de gráficas
        |--------------------------------------------------------------------------
        */

        $patientChartLabels = $patientChartLabels ?? ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'];
        $patientChartData = $patientChartData ?? [0, 0, 0, 0, 0, $totalPatients];

        $appointmentChartLabels = $appointmentChartLabels ?? ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        $appointmentChartData = $appointmentChartData ?? [0, 0, 0, 0, 0, 0, $todayAppointmentsCount];

        $incomeChartLabels = $incomeChartLabels ?? ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'];
        $incomeChartData = $incomeChartData ?? [0, 0, 0, 0, 0, $monthIncome];

        /*
        |--------------------------------------------------------------------------
        | Crecimiento
        |--------------------------------------------------------------------------
        */

        $patientGrowth = $patientGrowth ?? 0;
        $appointmentGrowth = $appointmentGrowth ?? 0;
        $incomeGrowth = $incomeGrowth ?? 0;
    @endphp


    {{-- =================================================
         PRUEBA GRATUITA DE 7 DÍAS
         Antes estaba fuera de @section y se imprimía antes del
         <!DOCTYPE>. Ahora lee la tabla `subscriptions`
         (status = en_prueba) desde DashboardController.
         ================================================= --}}
    @if($isOnTrial ?? false)


    <div class="trial-alert">

        <div class="trial-alert-icon">
            <i class="fas fa-seedling"></i>
        </div>

        <div class="trial-alert-content">

            <span class="trial-alert-label">
                PRUEBA GRATUITA
            </span>

            @if($isTrialActive)

                <strong>
                    Te quedan {{ $trialDaysRemaining }} días
                </strong>

                <p>
                    Estás usando NutriLife en modo de prueba.
                    Explora todas las herramientas disponibles.
                </p>

            @else

                <strong>
                    Tu prueba gratuita ha terminado
                </strong>

                <p>
                    Selecciona un plan para continuar usando NutriLife.
                </p>

            @endif

        </div>

        <a
            href="{{ route('landing') }}#planes"
            class="trial-alert-button"
        >
            Ver planes
            <i class="fas fa-arrow-right"></i>
        </a>

    </div>

    @endif

    {{-- =====================================================
         DECORACIÓN GENERAL
         ===================================================== --}}
    <div class="dashboard-background">

        <div class="dashboard-background__blob dashboard-background__blob--one"></div>
        <div class="dashboard-background__blob dashboard-background__blob--two"></div>
        <div class="dashboard-background__blob dashboard-background__blob--three"></div>


        {{-- =================================================
             HERO
             ================================================= --}}
        @include('dashboard.partials.hero')


        {{-- =================================================
             MÉTRICAS PRINCIPALES
             ================================================= --}}
        @include('dashboard.partials.metrics')


        {{-- =================================================
             GRÁFICAS
             ================================================= --}}
        @include('dashboard.partials.charts')


        {{-- =================================================
             RESUMEN + ACCIONES RÁPIDAS
             ================================================= --}}
        <div class="dashboard-bottom-grid">
            @include('dashboard.partials.summary')
            @include('dashboard.partials.quick-actions')
        </div>


        {{-- =================================================
             FOOTER
             ================================================= --}}
        @include('dashboard.partials.footer')

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



    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    {{-- Solo pasamos los datos del servidor a JS; toda la lógica
         de las gráficas vive en resources/js/dashboard.js --}}
    <script>
        window.dashboardData = {
            patients: {
                labels: @json($patientChartLabels),
                data: @json($patientChartData),
            },
            patientStatus: {
                active: {{ $activePatients }},
                inactive: {{ $inactivePatients }},
            },
            appointments: {
                labels: @json($appointmentChartLabels),
                data: @json($appointmentChartData),
            },
            income: {
                labels: @json($incomeChartLabels),
                data: @json($incomeChartData),
            },
        };
    </script>

    @vite(['resources/js/dashboard.js'])

@stop
