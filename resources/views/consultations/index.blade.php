@extends('adminlte::page')

@section('title', 'Consultas | NutriLife')


{{-- =========================================================
     CONTENT HEADER
     ========================================================= --}}
@section('content_header')

<div class="consultations-header">

    <div>

        <span class="consultation-eyebrow">
            <i class="fas fa-leaf me-1"></i>
            NUTRILIFE
        </span>

        <h1>Consultas</h1>

        <p>
            Registra y consulta el historial clínico de tus pacientes.
        </p>

    </div>


    <a
        href="{{ route('consultas.create') }}"
        class="btn-consultation-primary"
    >
        <i class="fas fa-plus"></i>

        Nueva consulta
    </a>

</div>

@stop


{{-- =========================================================
     CONTENT
     ========================================================= --}}
@section('content')

<div class="consultations-page">


    @if(session('success'))

        <div class="consultation-alert">

            <i class="fas fa-check-circle"></i>

            {{ session('success') }}

        </div>

    @endif



    {{-- MÉTRICAS --}}

    <div class="consultation-stats">


        <div class="consultation-stat">

            <div class="consultation-stat-icon">

                <i class="fas fa-stethoscope"></i>

            </div>

            <div>

                <span>
                    Consultas del mes
                </span>

                <h3>
                    {{ $consultationsThisMonth }}
                </h3>

            </div>

        </div>



        <div class="consultation-stat">

            <div class="consultation-stat-icon today">

                <i class="far fa-calendar-check"></i>

            </div>

            <div>

                <span>
                    Consultas de hoy
                </span>

                <h3>
                    {{ $consultationsToday }}
                </h3>

            </div>

        </div>



        <div class="consultation-stat">

            <div class="consultation-stat-icon patients">

                <i class="fas fa-user-check"></i>

            </div>

            <div>

                <span>
                    Pacientes atendidos
                </span>

                <h3>
                    {{ $patientsAttended }}
                </h3>

            </div>

        </div>


    </div>



    {{-- HISTORIAL --}}

    <div class="consultations-card">


        <div class="consultations-card-header">

            <div>

                <span>
                    HISTORIAL CLÍNICO
                </span>

                <h4>
                    Consultas registradas
                </h4>

            </div>


            {{-- Segundo acceso al mismo flujo que "Nueva consulta" (consultas.create) --}}
            <a href="{{ route('consultas.create') }}" class="consultations-card-header__action" title="Nueva consulta" aria-label="Registrar nueva consulta">
                <i class="fas fa-notes-medical" aria-hidden="true"></i>
            </a>

        </div>



        {{-- Filtro por paciente (?paciente=): ficha del paciente o detalle de consulta --}}
        @if($patientFilter)
            <div class="consultation-filter" role="status">
                <span>
                    <i class="fas fa-user" aria-hidden="true"></i>
                    Consultas de <strong>{{ $patientFilter->first_name }} {{ $patientFilter->last_name }}</strong>
                </span>
                <a href="{{ route('consultas.index') }}">
                    <i class="fas fa-times" aria-hidden="true"></i>
                    Ver todas
                </a>
            </div>
        @elseif($invalidPatientFilter)
            <div class="consultation-filter is-warning" role="alert">
                <span>
                    <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                    El paciente indicado no existe o no pertenece a tu consultorio. Se muestran todas tus consultas.
                </span>
            </div>
        @endif

        @if($consultations->isEmpty())


            <div class="empty-consultations">

                <i class="fas fa-stethoscope"></i>

                <h5>
                    {{ $patientFilter ? 'Este paciente aún no tiene consultas' : 'Sin consultas registradas' }}
                </h5>

                <p>
                    Cuando atiendas a un paciente,
                    registra aquí la consulta.
                </p>

                <a href="{{ route('consultas.create') }}">
                    {{ $patientFilter ? 'Registrar consulta' : 'Registrar primera consulta' }}
                </a>

            </div>


        @else


            <div class="consultations-list">


                @foreach($consultations as $consultation)

                    <div class="consultation-item">


                        <div class="consultation-date">

                            <strong>
                                {{ $consultation->consultation_at->format('d') }}
                            </strong>

                            <span>
                                {{ strtoupper(
                                    $consultation->consultation_at
                                        ->locale('es')
                                        ->translatedFormat('M')
                                ) }}
                            </span>

                        </div>



                        <div class="consultation-info">

                            <h5>

                                {{ $consultation->patient->first_name }}
                                {{ $consultation->patient->last_name }}

                            </h5>

                            <p>

                                {{ $consultation->reason
                                    ?? 'Consulta nutricional' }}

                            </p>


                            <div class="consultation-meta">

                                <span>

                                    <i class="far fa-clock"></i>

                                    {{
                                        $consultation
                                            ->consultation_at
                                            ->format('H:i')
                                    }}

                                </span>


                                @if($consultation->next_visit)

                                    <span>

                                        <i class="far fa-calendar"></i>

                                        Próxima:

                                        {{
                                            $consultation
                                                ->next_visit
                                                ->format('d/m/Y')
                                        }}

                                    </span>

                                @endif

                            </div>

                        </div>



                        <div class="consultation-actions">

                            <a
                                href="{{ route('consultas.show', $consultation) }}"
                                class="consultation-view-btn"
                                aria-label="Ver consulta de {{ $consultation->patient?->first_name }} {{ $consultation->patient?->last_name }} del {{ $consultation->consultation_at->format('d/m/Y') }}"
                            >

                                <i class="fas fa-eye" aria-hidden="true"></i>

                                Ver consulta

                            </a>

                        </div>


                    </div>

                @endforeach


            </div>


        @endif


    </div>


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


@stop
