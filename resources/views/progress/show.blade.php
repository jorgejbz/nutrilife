@extends('adminlte::page')

@section('title', 'Progreso del paciente | NutriLife')


{{-- =========================================================
     CONTENT HEADER
     ========================================================= --}}
@section('content_header')

<div class="progress-detail-header">

    <div>

        <span class="progress-eyebrow">
            <i class="fas fa-leaf me-1"></i>
            NUTRILIFE
        </span>

        <h1>
            {{ $paciente->first_name }}
            {{ $paciente->last_name }}
        </h1>

        <p>
            Evolución y seguimiento nutricional.
        </p>

    </div>


    <div class="d-flex flex-wrap gap-2">

        <a
            href="{{ route('evaluaciones.create', $paciente) }}"
            class="btn-progress-add"
        >
            <i class="fas fa-plus"></i>
            Nueva evaluación
        </a>

        <a
            href="{{ route('progreso.index') }}"
            class="btn-progress-back"
        >
            <i class="fas fa-arrow-left"></i>
            Regresar
        </a>

    </div>

</div>

@stop


{{-- =========================================================
     CONTENT
     ========================================================= --}}
@section('content')

<div class="progress-detail-page">


    @if($evaluations->isEmpty())

        <div class="progress-empty-detail">

            <i class="fas fa-chart-line"></i>

            <h4>
                Sin información de progreso
            </h4>

            <p>
                Registra la primera evaluación de este paciente.
            </p>

            <a href="{{ route('evaluaciones.create', $paciente) }}">
                Registrar evaluación
            </a>

        </div>

    @else


        {{-- MÉTRICAS --}}

        <div class="detail-stats">


            <div class="detail-stat">

                <span>Peso actual</span>

                <strong>
                    {{ number_format($latestEvaluation->weight, 1) }}
                    kg
                </strong>

                <small>
                    @if(!is_null($weightChange))

                        {{ $weightChange > 0 ? '+' : '' }}
                        {{ $weightChange }} kg

                        desde el inicio

                    @else
                        Sin comparativa
                    @endif
                </small>

            </div>



            <div class="detail-stat">

                <span>Grasa corporal</span>

                <strong>

                    @if(!is_null($latestEvaluation->body_fat))

                        {{ number_format($latestEvaluation->body_fat, 1) }}%

                    @else

                        —

                    @endif

                </strong>

                <small>

                    @if(!is_null($fatChange))

                        {{ $fatChange > 0 ? '+' : '' }}
                        {{ $fatChange }}%

                        desde el inicio

                    @else
                        Sin comparativa
                    @endif

                </small>

            </div>



            <div class="detail-stat">

                <span>Masa muscular</span>

                <strong>

                    @if(!is_null($latestEvaluation->muscle_mass))

                        {{ number_format($latestEvaluation->muscle_mass, 1) }}
                        kg

                    @else

                        —

                    @endif

                </strong>

                <small>

                    @if(!is_null($muscleChange))

                        {{ $muscleChange > 0 ? '+' : '' }}
                        {{ $muscleChange }} kg

                        desde el inicio

                    @else
                        Sin comparativa
                    @endif

                </small>

            </div>



            <div class="detail-stat">

                <span>Evaluaciones</span>

                <strong>
                    {{ $evaluations->count() }}
                </strong>

                <small>
                    Seguimientos registrados
                </small>

            </div>


        </div>



        {{-- GRÁFICAS --}}

        <div class="charts-grid">


            <div class="progress-chart-card">

                <div class="chart-title">

                    <div>
                        <span>EVOLUCIÓN</span>
                        <h4>Peso corporal</h4>
                    </div>

                    <i class="fas fa-weight"></i>

                </div>

                <div class="chart-container">
                    <canvas id="weightChart"></canvas>
                </div>

            </div>



            <div class="progress-chart-card">

                <div class="chart-title">

                    <div>
                        <span>COMPOSICIÓN CORPORAL</span>
                        <h4>Grasa y masa muscular</h4>
                    </div>

                    <i class="fas fa-chart-area"></i>

                </div>

                <div class="chart-container">
                    <canvas id="compositionChart"></canvas>
                </div>

            </div>


        </div>



        {{-- HISTORIAL --}}

        <div class="history-card">


            <div class="chart-title">

                <div>

                    <span>HISTORIAL</span>

                    <h4>
                        Evaluaciones registradas
                    </h4>

                </div>

                <i class="fas fa-clipboard-list"></i>

            </div>


            <div class="table-responsive">

                <table class="table progress-history-table">

                    <thead>

                        <tr>
                            <th>Fecha</th>
                            <th>Peso</th>
                            <th>IMC</th>
                            <th>Grasa</th>
                            <th>Músculo</th>
                            <th>Cintura</th>
                        </tr>

                    </thead>

                    <tbody>

                        @foreach($evaluations->sortByDesc('evaluation_date') as $evaluation)

                            <tr>

                                <td>
                                    {{ $evaluation->evaluation_date->format('d/m/Y') }}
                                </td>

                                <td>
                                    {{ $evaluation->weight ?? '—' }}
                                    kg
                                </td>

                                <td>
                                    {{ $evaluation->bmi ?? '—' }}
                                </td>

                                <td>
                                    {{ $evaluation->body_fat ?? '—' }}
                                    %
                                </td>

                                <td>
                                    {{ $evaluation->muscle_mass ?? '—' }}
                                    kg
                                </td>

                                <td>
                                    {{ $evaluation->waist ?? '—' }}
                                    cm
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>


    @endif


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



    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    {{-- Solo pasamos los datos del servidor a JS; las gráficas
         viven en resources/js/pages/progress-show.js --}}
    <script>
        window.progressShowData = {
            labels: @json($labels),
            weights: @json($weights),
            bodyFat: @json($bodyFat),
            muscleMass: @json($muscleMass),
        };
    </script>

    @vite(['resources/js/pages/progress-show.js'])

@stop
