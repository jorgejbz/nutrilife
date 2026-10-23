@extends('adminlte::page')

@section('title', 'Consulta | NutriLife')

{{-- =========================================================
     DETALLE DE CONSULTA (consultas.show)
     Destino de «Ver consulta» del historial. Solo lectura: el
     módulo no permite editar consultas registradas.
     ========================================================= --}}
@php
    $patient = $consultation->patient;
    $patientName = $patient ? $patient->first_name.' '.$patient->last_name : 'Paciente eliminado';
@endphp

@section('content_header')

<div class="consultations-header">

    <div>
        <span class="consultation-eyebrow">
            <i class="fas fa-leaf me-1"></i>
            NUTRILIFE
        </span>

        <h1>Consulta de {{ $patientName }}</h1>

        <p>
            {{ $consultation->consultation_at->locale('es')->translatedFormat('l j \d\e F \d\e Y') }}
            · {{ $consultation->consultation_at->format('H:i') }}
        </p>
    </div>

    <a href="{{ route('consultas.index') }}" class="btn-consultation-primary">
        <i class="fas fa-arrow-left"></i>
        Regresar
    </a>

</div>

@stop

@section('content')

<div class="consultations-page">

    @if(session('success'))
        <div class="consultation-alert" role="status">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="consultation-alert is-error" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            {{ session('error') }}
        </div>
    @endif

    <div class="consultations-card">

        <div class="consultations-card-header">
            <div>
                <span>HISTORIAL CLÍNICO</span>
                <h4>Detalle de la consulta</h4>
            </div>
        </div>

        <div class="consultation-detail-body">

            <div class="consultation-detail-grid">

                <div class="consultation-detail-field">
                    <span>Paciente</span>
                    <strong>
                        @if($patient && ! $patient->trashed())
                            <a href="{{ route('pacientes.show', $patient) }}">{{ $patientName }}</a>
                        @else
                            {{ $patientName }}
                        @endif
                    </strong>
                    @if($patient?->trashed())
                        <small>El expediente del paciente fue eliminado.</small>
                    @endif
                </div>

                <div class="consultation-detail-field">
                    <span>Fecha y hora</span>
                    <strong>{{ $consultation->consultation_at->format('d/m/Y H:i') }}</strong>
                </div>

                <div class="consultation-detail-field">
                    <span>Motivo</span>
                    <strong>{{ $consultation->reason ?: 'Consulta nutricional' }}</strong>
                </div>

                <div class="consultation-detail-field">
                    <span>Cita relacionada</span>
                    @if($consultation->appointment)
                        <strong>{{ $consultation->appointment->appointment_at->format('d/m/Y H:i') }}</strong>
                        <small>@include('consultations.partials.appointment-status', ['status' => $consultation->appointment->status])</small>
                    @else
                        <strong>—</strong>
                        <small>Registrada sin cita previa.</small>
                    @endif
                </div>

                <div class="consultation-detail-field">
                    <span>Próxima consulta</span>
                    @if($consultation->next_visit)
                        <strong>{{ $consultation->next_visit->format('d/m/Y') }}</strong>
                        @if($nextAppointments->isEmpty())
                            <small>Sin cita agendada en el calendario para ese día.</small>
                        @else
                            <small>{{ $nextAppointments->count() === 1 ? 'Su cita está abajo.' : $nextAppointments->count().' citas ese día, abajo.' }}</small>
                        @endif
                    @else
                        <strong>—</strong>
                        <small>No se indicó próxima consulta.</small>
                    @endif
                </div>

                <div class="consultation-detail-field consultation-detail-text">
                    <span>Observaciones</span>
                    <p>{{ $consultation->observations ?: 'Sin observaciones registradas.' }}</p>
                </div>

                <div class="consultation-detail-field consultation-detail-text">
                    <span>Recomendaciones</span>
                    <p>{{ $consultation->recommendations ?: 'Sin recomendaciones registradas.' }}</p>
                </div>

            </div>

            {{-- CITA DE LA PRÓXIMA CONSULTA: estado y acciones (mismas rutas
                 que el calendario; `consulta` hace que regresen aquí). --}}
            @if($consultation->next_visit)
                <section class="consultation-next" aria-labelledby="nextAppointmentTitle">
                    <h5 id="nextAppointmentTitle">
                        <i class="far fa-calendar-check" aria-hidden="true"></i>
                        Cita de la próxima consulta
                    </h5>

                    @forelse($nextAppointments as $appointment)
                        @php
                            $isOpen = in_array($appointment->status, ['Pendiente', 'Confirmada'], true);
                            $hasLinked = $appointment->has_payments || $appointment->has_consultations;
                        @endphp

                        <div class="consultation-next-item">
                            <div class="consultation-next-info">
                                @include('consultations.partials.appointment-status', ['status' => $appointment->status])
                                <strong>{{ $appointment->appointment_at->locale('es')->translatedFormat('l j \d\e F') }} · {{ $appointment->appointment_at->format('H:i') }}</strong>
                                <small>
                                    {{ $appointment->reason ?: 'Sin motivo' }} ·
                                    <a href="{{ route('citas.index', ['dia' => $appointment->appointment_at->toDateString()]) }}">ver en el calendario</a>
                                </small>
                            </div>

                            <div class="consultation-next-actions">
                                @if($isOpen)
                                    <form action="{{ route('citas.cancel', $appointment) }}"
                                          method="POST"
                                          data-confirm="La cita del {{ $appointment->appointment_at->format('d/m/Y H:i') }} quedará marcada como «Cancelada». Seguirá en el historial."
                                          data-confirm-title="Cancelar cita"
                                          data-confirm-accept="Cancelar cita"
                                          data-confirm-variant="danger"
                                          data-confirm-icon="fa-ban">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="consulta" value="{{ $consultation->id }}">
                                        <button type="submit" class="consultation-action-btn">
                                            <i class="fas fa-ban" aria-hidden="true"></i>
                                            Cancelar
                                        </button>
                                    </form>
                                @endif

                                @if($hasLinked)
                                    <span class="consultation-next-note" title="Tiene pagos o consultas registrados">
                                        <i class="fas fa-lock" aria-hidden="true"></i>
                                        No se puede eliminar (tiene {{ $appointment->has_payments ? 'pagos' : 'consultas' }} ligados)
                                    </span>
                                @else
                                    <form action="{{ route('citas.destroy', $appointment) }}"
                                          method="POST"
                                          data-confirm="La cita del {{ $appointment->appointment_at->format('d/m/Y H:i') }} se eliminará definitivamente del calendario."
                                          data-confirm-title="Eliminar cita"
                                          data-confirm-accept="Eliminar"
                                          data-confirm-variant="danger"
                                          data-confirm-icon="fa-trash-alt">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="consulta" value="{{ $consultation->id }}">
                                        <button type="submit" class="consultation-action-btn is-danger">
                                            <i class="far fa-trash-alt" aria-hidden="true"></i>
                                            Eliminar
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="consultation-next-empty">
                            No hay cita en el calendario para el {{ $consultation->next_visit->format('d/m/Y') }}.
                            <a href="{{ route('citas.create') }}">Agendar cita</a>
                        </p>
                    @endforelse
                </section>
            @endif

            <div class="consultation-detail-actions">
                @if($patient)
                    <a href="{{ route('consultas.index', ['paciente' => $patient->id]) }}" class="consultation-view-btn">
                        <i class="fas fa-list" aria-hidden="true"></i>
                        Ver consultas de este paciente
                    </a>
                @endif

                <a href="{{ route('consultas.index') }}" class="consultation-view-btn">
                    <i class="fas fa-stethoscope" aria-hidden="true"></i>
                    Todas las consultas
                </a>
            </div>

        </div>

    </div>

</div>

@stop

@section('css')

    {{-- Estilos del panel: tokens + public/css/admin/* + temas + responsive (config/stylesheets.php),
         anti-parpadeo del tema, tipografía e iconos --}}
    @include('partials.theme-styles', ['section' => 'admin'])

@stop
