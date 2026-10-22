@extends('adminlte::page')

{{-- =========================================================
     CONSULTAS DE LA CLÍNICA (/clinica/consultas)
     Historial de solo lectura de la cuenta principal y sus
     subperfiles. Datos: ClinicConsultationController@index.
     ========================================================= --}}

@section('title', 'Consultas de la clínica | NutriLife')

@section('content_header')
    <div class="patients-header">
        <div>
            <span class="header-eyebrow">
                <i class="fas fa-leaf me-1"></i>
                NUTRILIFE · CLÍNICA
            </span>
            <h1>Consultas de la clínica</h1>
            <p>Consultas registradas por todos los nutriólogos de {{ $clinic->name }}.</p>
        </div>
    </div>
@stop

@section('content')
<div class="nl-profile">

    <section class="nl-profile-card">
        <form method="get" action="{{ route('clinic.consultations.index') }}" class="nl-toolbar" role="search">
            <div class="nl-field">
                <label for="nutriologo">Nutriólogo</label>
                <div class="nl-input">
                    <i class="fas fa-user-doctor" aria-hidden="true"></i>
                    <select id="nutriologo" name="nutriologo">
                        <option value="">Todos</option>
                        @foreach ($nutritionists as $nutritionist)
                            <option value="{{ $nutritionist->id }}" @selected($filters['nutriologo'] === $nutritionist->id)>
                                {{ $nutritionist->name }}{{ $nutritionist->id === $clinic->id ? ' (cuenta principal)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="nl-field">
                <label for="paciente">Paciente</label>
                <div class="nl-input">
                    <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                    <input id="paciente" name="paciente" type="search" value="{{ $filters['paciente'] }}" placeholder="Nombre o apellidos">
                </div>
            </div>
            <div class="nl-field">
                <label for="desde">Desde</label>
                <div class="nl-input">
                    <i class="far fa-calendar" aria-hidden="true"></i>
                    <input id="desde" name="desde" type="date" value="{{ $filters['desde'] }}">
                </div>
            </div>
            <div class="nl-field">
                <label for="hasta">Hasta</label>
                <div class="nl-input">
                    <i class="far fa-calendar" aria-hidden="true"></i>
                    <input id="hasta" name="hasta" type="date" value="{{ $filters['hasta'] }}">
                </div>
            </div>
            <div class="nl-toolbar-actions">
                <button type="submit" class="nl-btn nl-btn--primary" data-loading-text="Filtrando…"><i class="fas fa-filter" aria-hidden="true"></i> Filtrar</button>
                <a href="{{ route('clinic.consultations.index') }}" class="nl-btn nl-btn--ghost{{ $hasFilters ? '' : ' is-disabled' }}" @unless ($hasFilters) aria-disabled="true" @endunless>
                    <i class="fas fa-eraser" aria-hidden="true"></i> Limpiar filtros
                </a>
            </div>
        </form>

        <p class="nl-result-summary" role="status">
            {{ $consultations->total() }} {{ $consultations->total() === 1 ? 'consulta' : 'consultas' }}
            @if ($filters['desde'] && $filters['hasta'])
                del {{ \Illuminate\Support\Carbon::parse($filters['desde'])->format('d/m/Y') }} al {{ \Illuminate\Support\Carbon::parse($filters['hasta'])->format('d/m/Y') }}
            @elseif ($filters['desde'])
                desde el {{ \Illuminate\Support\Carbon::parse($filters['desde'])->format('d/m/Y') }}
            @elseif ($filters['hasta'])
                hasta el {{ \Illuminate\Support\Carbon::parse($filters['hasta'])->format('d/m/Y') }}
            @endif
        </p>

        @if ($consultations->isEmpty())
            <p class="nl-empty"><i class="fas fa-notes-medical" aria-hidden="true"></i>No hay consultas con esos filtros.</p>
        @else
            <div class="table-responsive">
                <table class="nl-table">
                    <thead>
                        <tr>
                            <th scope="col">Fecha y hora</th>
                            <th scope="col">Paciente</th>
                            <th scope="col">Nutriólogo</th>
                            <th scope="col">Motivo</th>
                            <th scope="col">Próxima visita</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($consultations as $consultation)
                            <tr>
                                <td data-label="Fecha">
                                    <strong>{{ $consultation->consultation_at->format('d/m/Y') }}</strong>
                                    <small>{{ $consultation->consultation_at->format('H:i') }}</small>
                                </td>
                                <td data-label="Paciente">
                                    {{-- El expediente solo lo abre su nutriólogo (pacientes.show) --}}
                                    @if ($consultation->patient && ! $consultation->patient->trashed())
                                        <a href="{{ route('pacientes.show', $consultation->patient) }}">{{ $consultation->patient->first_name }} {{ $consultation->patient->last_name }}</a>
                                    @else
                                        {{ $consultation->patient?->first_name }} {{ $consultation->patient?->last_name }}
                                    @endif
                                </td>
                                <td data-label="Nutriólogo">
                                    @if ($consultation->user_id === $clinic->id)
                                        {{ $consultation->user?->name }} <small>Cuenta principal</small>
                                    @else
                                        <a href="{{ route('clinic.team.show', $consultation->user_id) }}">{{ $consultation->user?->name }}</a>
                                    @endif
                                </td>
                                <td data-label="Motivo">{{ $consultation->reason ?: '—' }}</td>
                                <td data-label="Próxima visita">{{ $consultation->next_visit?->format('d/m/Y') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @include('partials.pager', ['paginator' => $consultations])
    </section>

</div>
@stop

@section('css')
    @include('partials.theme-styles', ['section' => 'admin'])
@stop
