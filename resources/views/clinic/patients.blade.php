@extends('adminlte::page')

{{-- =========================================================
     PACIENTES DE LA CLÍNICA (/clinica/pacientes)
     Vista general de solo lectura con el nutriólogo responsable de
     cada expediente. Datos: ClinicPatientController@index.
     ========================================================= --}}

@section('title', 'Pacientes de la clínica | NutriLife')

@php
    // Iniciales sin títulos (Dr., Lic.…), como en el sidebar.
    $initials = fn (?string $name) => collect(preg_split('/\s+/', trim((string) $name)))
        ->reject(fn ($part) => in_array(mb_strtolower(rtrim($part, '.')), ['dr', 'dra', 'lic', 'mtra', 'mtro'], true))
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('') ?: 'N';
@endphp

@section('content_header')
    <div class="patients-header">
        <div>
            <span class="header-eyebrow">
                <i class="fas fa-leaf me-1"></i>
                NUTRILIFE · CLÍNICA
            </span>
            <h1>Pacientes de la clínica</h1>
            <p>Todos los expedientes de {{ $clinic->name }} y su nutriólogo responsable.</p>
        </div>
    </div>
@stop

@section('content')
<div class="nl-profile">

    <section class="nl-profile-card">
        <form method="get" action="{{ route('clinic.patients.index') }}" class="nl-toolbar" role="search">
            <div class="nl-field nl-field--wide">
                <label for="q">Paciente</label>
                <div class="nl-input">
                    <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                    <input id="q" name="q" type="search" value="{{ $filters['q'] }}" placeholder="Nombre, apellidos o correo">
                </div>
            </div>
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
                <label for="estado">Estado</label>
                <div class="nl-input">
                    <i class="fas fa-toggle-on" aria-hidden="true"></i>
                    <select id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="activo" @selected($filters['estado'] === 'activo')>Activos</option>
                        <option value="inactivo" @selected($filters['estado'] === 'inactivo')>Inactivos</option>
                    </select>
                </div>
            </div>
            <div class="nl-toolbar-actions">
                <button type="submit" class="nl-btn nl-btn--primary" data-loading-text="Filtrando…"><i class="fas fa-filter" aria-hidden="true"></i> Filtrar</button>
                <a href="{{ route('clinic.patients.index') }}" class="nl-btn nl-btn--ghost">Limpiar</a>
            </div>
        </form>

        {{-- PACIENTES POR NUTRIÓLOGO: filtra conservando búsqueda y estado --}}
        @php
            $keepFilters = array_filter(['q' => $filters['q'] !== '' ? $filters['q'] : null, 'estado' => $filters['estado']]);
        @endphp
        <nav class="nl-quick-days" aria-label="Pacientes por nutriólogo">
            <span class="nl-quick-days__label"><i class="fas fa-user-doctor" aria-hidden="true"></i> Por nutriólogo:</span>
            <a href="{{ route('clinic.patients.index', $keepFilters) }}"
               class="nl-quick-day{{ $filters['nutriologo'] === null ? ' is-active' : '' }}"
               @if ($filters['nutriologo'] === null) aria-current="true" @endif>
                <strong>Todos</strong>
                <small>{{ $patientCounts->sum() }}</small>
            </a>
            @foreach ($nutritionists as $nutritionist)
                <a href="{{ route('clinic.patients.index', $keepFilters + ['nutriologo' => $nutritionist->id]) }}"
                   class="nl-quick-day{{ $filters['nutriologo'] === $nutritionist->id ? ' is-active' : '' }}"
                   @if ($filters['nutriologo'] === $nutritionist->id) aria-current="true" @endif>
                    <strong>{{ $nutritionist->name }}</strong>
                    <small>{{ $patientCounts->get($nutritionist->id, 0) }} {{ $nutritionist->id === $clinic->id ? '· principal' : '' }}</small>
                </a>
            @endforeach
        </nav>

        @if ($patients->isEmpty())
            <p class="nl-empty"><i class="fas fa-users" aria-hidden="true"></i>No hay pacientes con esos filtros.</p>
        @else
            <div class="table-responsive">
                <table class="nl-table">
                    <thead>
                        <tr>
                            <th scope="col">Paciente</th>
                            <th scope="col">Nutriólogo responsable</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Próxima cita</th>
                            <th scope="col" class="is-num">Citas</th>
                            <th scope="col" class="is-num">Planes activos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($patients as $patient)
                            <tr>
                                <td data-label="Paciente">
                                    {{-- La cuenta principal abre el expediente de cualquier paciente de su clínica --}}
                                    <a href="{{ route('pacientes.show', $patient) }}"><strong>{{ $patient->first_name }} {{ $patient->last_name }}</strong></a>
                                    <small>{{ $patient->email }}</small>
                                </td>
                                <td data-label="Responsable">
                                    @php $isMain = $patient->user_id === $clinic->id; @endphp
                                    <div class="nl-owner{{ $isMain ? ' is-main' : '' }}">
                                        <span class="nl-owner__avatar" aria-hidden="true">{{ $initials($patient->user?->name) }}</span>
                                        <span class="nl-owner__info">
                                            @if ($isMain)
                                                <strong>{{ $patient->user?->name }}</strong>
                                            @else
                                                <a href="{{ route('clinic.team.show', $patient->user_id) }}">{{ $patient->user?->name }}</a>
                                            @endif
                                            <small>{{ $isMain ? 'Cuenta principal' : 'Subperfil de la clínica' }}</small>
                                        </span>
                                    </div>
                                </td>
                                <td data-label="Estado">
                                    <span class="nl-status nl-status--{{ $patient->active ? 'activa' : 'vencida' }}">{{ $patient->active ? 'Activo' : 'Inactivo' }}</span>
                                </td>
                                <td data-label="Próxima cita">
                                    {{ $patient->next_appointment_at ? \Illuminate\Support\Carbon::parse($patient->next_appointment_at)->format('d/m/Y H:i') : 'Sin cita' }}
                                </td>
                                <td data-label="Citas" class="is-num">{{ $patient->appointments_count }}</td>
                                <td data-label="Planes activos" class="is-num">{{ $patient->active_diet_plans_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @include('partials.pager', ['paginator' => $patients])
    </section>

</div>
@stop

@section('css')
    @include('partials.theme-styles', ['section' => 'admin'])
@stop
