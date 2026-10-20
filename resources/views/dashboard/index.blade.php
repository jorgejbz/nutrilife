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

    {{-- Todos los indicadores vienen de DashboardController
         (App\Services\DashboardStats): no hay valores fijos en la vista. --}}

    @if(session('success'))
        <div class="consultation-alert" role="status">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif


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
                    @if($trialDaysRemaining === 0)
                        Hoy es el último día de tu prueba
                    @elseif($trialDaysRemaining === 1)
                        Te queda 1 día de prueba
                    @else
                        Te quedan {{ $trialDaysRemaining }} días de prueba
                    @endif
                </strong>

                <p>
                    Estás usando NutriLife con la prueba gratuita de {{ config('nutrilife.trial_days') }} días
                    (termina el {{ $trialEndsAt->format('d/m/Y \a \l\a\s H:i') }}).
                    Explora todas las herramientas y elige un plan cuando quieras.
                </p>

            @else

                <strong>
                    Tu prueba gratuita ha terminado
                </strong>

                <p>
                    Tus pacientes y tu información siguen guardados.
                    Elige un plan para continuar usando NutriLife.
                </p>

            @endif

        </div>

        <a
            href="{{ route('subscription.plans') }}"
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
        window.dashboardData = @json($charts);
    </script>

    @vite(['resources/js/dashboard.js'])

@stop
