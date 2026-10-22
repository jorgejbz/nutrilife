@extends('adminlte::page')

{{-- =========================================================
     AGENDA DE LA CLÍNICA (/clinica/agenda)
     Citas de todos los profesionales con filtros por nutriólogo,
     paciente, fechas y estado. Datos: ClinicAgendaController@index.
     ========================================================= --}}

@section('title', 'Agenda de la clínica | NutriLife')

@php
    $statusClass = fn (string $status) => \Illuminate\Support\Str::slug($status);
@endphp

@section('content_header')
    <div class="patients-header">
        <div>
            <span class="header-eyebrow">
                <i class="fas fa-leaf me-1"></i>
                NUTRILIFE · CLÍNICA
            </span>
            <h1>Agenda de la clínica</h1>
            <p>Citas de todos los nutriólogos de {{ $clinic->name }}.</p>
        </div>
    </div>
@stop

@section('content')
<div class="nl-profile">

    <section class="nl-profile-card">
        <form method="get" action="{{ route('clinic.agenda.index') }}" class="nl-toolbar" role="search">
            <div class="nl-field">
                <label for="nutriologo">Nutriólogo</label>
                <div class="nl-input">
                    <i class="fas fa-user-doctor" aria-hidden="true"></i>
                    <select id="nutriologo" name="nutriologo">
                        <option value="">Todos</option>
                        @foreach ($nutritionists as $nutritionist)
                            <option value="{{ $nutritionist->id }}" @selected($filters['nutriologo'] === $nutritionist->id)>{{ $nutritionist->name }}</option>
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
            <div class="nl-field">
                <label for="estado">Estado</label>
                <div class="nl-input">
                    <i class="fas fa-circle-half-stroke" aria-hidden="true"></i>
                    <select id="estado" name="estado">
                        <option value="">Todos</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected($filters['estado'] === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="nl-toolbar-actions">
                <button type="submit" class="nl-btn nl-btn--primary" data-loading-text="Filtrando…"><i class="fas fa-filter" aria-hidden="true"></i> Filtrar</button>
                {{-- Sin parámetros: el controlador vuelve a "de hoy en adelante" --}}
                <a href="{{ route('clinic.agenda.index') }}" class="nl-btn nl-btn--ghost{{ $hasFilters ? '' : ' is-disabled' }}" @unless ($hasFilters) aria-disabled="true" @endunless>
                    <i class="fas fa-eraser" aria-hidden="true"></i> Limpiar filtros
                </a>
            </div>
        </form>

        {{-- ACCESOS RÁPIDOS POR DÍA: reemplazan desde/hasta y conservan
             nutriólogo, paciente y estado. --}}
        @php
            $keepFilters = array_filter([
                'nutriologo' => $filters['nutriologo'],
                'paciente' => $filters['paciente'] !== '' ? $filters['paciente'] : null,
                'estado' => $filters['estado'],
            ]);
            $quickLabels = ['ayer' => 'Ayer', 'hoy' => 'Hoy', 'manana' => 'Mañana'];
            // APP_LOCALE=en: nombres en español sin depender de translatedFormat.
            $weekDays = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
            $monthNames = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
            $longDate = fn ($date) => $weekDays[$date->dayOfWeek].' '.$date->day.' de '.$monthNames[$date->month - 1];
        @endphp
        <nav class="nl-quick-days" aria-label="Citas por día">
            <span class="nl-quick-days__label"><i class="far fa-calendar-check" aria-hidden="true"></i> Ver día:</span>
            @foreach ($quickDays as $key => $date)
                <a href="{{ route('clinic.agenda.index', $keepFilters + ['dia' => $key]) }}"
                   class="nl-quick-day{{ $filters['dia'] === $key ? ' is-active' : '' }}"
                   @if ($filters['dia'] === $key) aria-current="true" @endif>
                    <strong>{{ $quickLabels[$key] }}</strong>
                    <small>{{ ucfirst(mb_substr($weekDays[$date->dayOfWeek], 0, 3)) }} {{ $date->format('d/m') }}</small>
                </a>
            @endforeach
        </nav>

        <p class="nl-result-summary" role="status">
            @if ($filters['dia'])
                Citas del {{ $longDate(\Illuminate\Support\Carbon::parse($filters['desde'])) }}
            @elseif ($filters['hasta'])
                Citas del {{ \Illuminate\Support\Carbon::parse($filters['desde'])->format('d/m/Y') }} al {{ \Illuminate\Support\Carbon::parse($filters['hasta'])->format('d/m/Y') }}
            @else
                Citas desde el {{ \Illuminate\Support\Carbon::parse($filters['desde'])->format('d/m/Y') }} en adelante
            @endif
            · {{ $appointments->total() }} {{ $appointments->total() === 1 ? 'cita' : 'citas' }}
        </p>

        @if ($appointments->isEmpty())
            <p class="nl-empty"><i class="far fa-calendar-xmark" aria-hidden="true"></i>No hay citas con esos filtros.</p>
        @else
            <div class="table-responsive">
                <table class="nl-table">
                    <thead>
                        <tr>
                            <th scope="col">Fecha y hora</th>
                            <th scope="col">Paciente</th>
                            <th scope="col">Nutriólogo</th>
                            <th scope="col">Motivo</th>
                            <th scope="col">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($appointments as $appointment)
                            <tr>
                                <td data-label="Fecha">
                                    <strong>{{ $appointment->appointment_at->format('d/m/Y') }}</strong>
                                    <small>{{ $appointment->appointment_at->format('H:i') }}</small>
                                </td>
                                <td data-label="Paciente">{{ $appointment->patient?->first_name }} {{ $appointment->patient?->last_name }}</td>
                                <td data-label="Nutriólogo">{{ $appointment->user?->name }}</td>
                                <td data-label="Motivo">{{ $appointment->reason ?: '—' }}</td>
                                <td data-label="Estado"><span class="nl-status nl-status--{{ $statusClass($appointment->status) }}">{{ $appointment->status }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @include('partials.pager', ['paginator' => $appointments])
    </section>

</div>
@stop

@section('css')
    @include('partials.theme-styles', ['section' => 'admin'])
@stop
