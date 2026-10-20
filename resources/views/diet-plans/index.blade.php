@extends('adminlte::page')

@section('title', 'Planes alimenticios | NutriLife')


{{-- =========================================================
     CONTENT HEADER
     ========================================================= --}}
@section('content_header')

    <div class="consultations-header">
        <div>
            <span class="consultation-eyebrow">
                <i class="fas fa-leaf mr-1"></i>
                NUTRILIFE
            </span>

            <h1>Planes alimenticios</h1>

            <p>Diseña los planes de tus pacientes y compara cada día contra sus objetivos.</p>
        </div>

        <a href="{{ route('diet-plans.create', $patientId ? ['paciente' => $patientId] : []) }}"
           class="btn-consultation-primary">
            <i class="fas fa-plus"></i>
            Nuevo plan
        </a>
    </div>

@stop


{{-- =========================================================
     CONTENT
     ========================================================= --}}
@section('content')

<div class="consultations-page diet-page">

    @if(session('success'))
        <div class="consultation-alert" role="status">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- MÉTRICAS --}}
    <div class="consultation-stats">
        <div class="consultation-stat">
            <div class="consultation-stat-icon">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div>
                <span>Planes registrados</span>
                <h3>{{ $totalPlans }}</h3>
            </div>
        </div>

        <div class="consultation-stat">
            <div class="consultation-stat-icon">
                <i class="fas fa-seedling"></i>
            </div>
            <div>
                <span>Planes activos</span>
                <h3>{{ $activePlans }}</h3>
            </div>
        </div>

        <div class="consultation-stat">
            <div class="consultation-stat-icon">
                <i class="fas fa-user-check"></i>
            </div>
            <div>
                <span>Pacientes con plan activo</span>
                <h3>{{ $patientsWithPlan }}</h3>
            </div>
        </div>
    </div>

    {{-- LISTA --}}
    <div class="consultations-card">

        <div class="consultations-card-header diet-list-header">
            <div>
                <span>NUTRICIÓN</span>
                <h4>Planes de tus pacientes</h4>
            </div>

            @if($totalPlans > 0)
                {{-- Filtros (GET): paciente y estado --}}
                <form method="GET" action="{{ route('diet-plans.index') }}" class="diet-filters" data-auto-submit>
                    <div class="diet-filter-search">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <label class="visually-hidden" for="filterSearch">Buscar plan</label>
                        <input type="search" name="q" id="filterSearch" value="{{ $search }}" placeholder="Buscar plan u objetivo…" maxlength="100">
                    </div>

                    <label class="visually-hidden" for="filterPatient">Paciente</label>
                    <div class="diet-filter-select">
                        <i class="far fa-user" aria-hidden="true"></i>
                        <select name="paciente" id="filterPatient">
                            <option value="">Todos los pacientes</option>
                            @foreach($patients as $patient)
                                <option value="{{ $patient->id }}" @selected($patientId === $patient->id)>
                                    {{ $patient->first_name }} {{ $patient->last_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="diet-filter-chips" role="group" aria-label="Estado del plan">
                        @foreach(['' => 'Todos', 'activos' => 'Activos', 'finalizados' => 'Finalizados'] as $value => $label)
                            <label class="diet-chip {{ ($status ?? '') === $value ? 'is-selected' : '' }}">
                                <input type="radio" name="estado" value="{{ $value }}" @checked(($status ?? '') === $value)>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>

                    <noscript><button type="submit" class="diet-btn diet-btn--ghost">Filtrar</button></noscript>
                </form>
            @endif
        </div>

        @if($totalPlans === 0)

            <div class="empty-consultations">
                <i class="fas fa-utensils"></i>
                <h5>Aún no tienes planes alimenticios</h5>
                <p>Crea el primero: eliges al paciente, defines sus objetivos y armas sus comidas con el catálogo de alimentos.</p>
                <a href="{{ route('diet-plans.create') }}">Crear primer plan</a>
            </div>

        @elseif($plans->isEmpty())

            <div class="empty-consultations">
                <i class="fas fa-filter"></i>
                <h5>Sin resultados</h5>
                <p>Ningún plan coincide con los filtros seleccionados.</p>
                <a href="{{ route('diet-plans.index') }}">Quitar filtros</a>
            </div>

        @else

            <div class="consultations-list">
                @foreach($plans as $plan)
                    @php
                        $totals = $plan->totals();
                        $progress = $plan->progress();
                        $mealCount = $plan->meals->count();
                        $patientName = $plan->patient
                            ? $plan->patient->first_name.' '.$plan->patient->last_name
                            : 'Paciente eliminado';
                    @endphp

                    <article class="consultation-item diet-plan-row {{ $plan->active ? '' : 'is-finished' }}">

                        <div class="consultation-date" aria-hidden="true">
                            <strong>{{ $plan->starts_at->format('d') }}</strong>
                            <span>{{ mb_strtoupper($plan->starts_at->locale('es')->translatedFormat('M')) }}</span>
                        </div>

                        <div class="consultation-info">
                            <div class="diet-row-title">
                                <h5>
                                    <a href="{{ route('diet-plans.show', $plan) }}">{{ $plan->name }}</a>
                                </h5>
                                <span class="diet-status {{ $plan->active ? 'is-active' : 'is-finished' }}">
                                    {{ $plan->statusLabel() }}
                                </span>
                            </div>

                            @if($plan->goal)
                                <p class="diet-row-goal"><i class="fas fa-bullseye" aria-hidden="true"></i> {{ $plan->goal }}</p>
                            @endif

                            <p>
                                <i class="far fa-user" aria-hidden="true"></i>
                                {{ $patientName }}
                                @if($plan->patient?->trashed())
                                    <span class="diet-muted">(dado de baja)</span>
                                @endif
                            </p>

                            <div class="diet-target-chips">
                                <span><i class="fas fa-fire" aria-hidden="true"></i>{{ number_format((float) $plan->target_kcal, 0) }} kcal</span>
                                <span>P {{ number_format((float) $plan->target_protein_g, 0) }} g</span>
                                <span>C {{ number_format((float) $plan->target_carbs_g, 0) }} g</span>
                                <span>G {{ number_format((float) $plan->target_lipids_g, 0) }} g</span>
                            </div>

                            <div class="diet-row-progress" title="Calorías armadas en las comidas contra el objetivo">
                                <div class="diet-bar diet-bar--sm {{ $progress['kcal'] > 105 ? 'is-over' : '' }}">
                                    <span style="width: {{ min($progress['kcal'], 100) }}%"></span>
                                </div>
                                <small>
                                    {{ number_format($totals['kcal'], 0) }} de {{ number_format((float) $plan->target_kcal, 0) }} kcal
                                    · {{ $mealCount }} {{ $mealCount === 1 ? 'comida' : 'comidas' }}
                                </small>
                            </div>

                            <div class="consultation-meta">
                                <span>
                                    <i class="far fa-calendar"></i>
                                    Desde {{ $plan->starts_at->format('d/m/Y') }}
                                </span>
                                <span>
                                    <i class="far fa-calendar-check"></i>
                                    {{ $plan->ends_at ? 'Hasta '.$plan->ends_at->format('d/m/Y') : 'Sin fecha de fin' }}
                                </span>
                            </div>
                        </div>

                        <div class="diet-row-actions">
                            <a href="{{ route('diet-plans.show', $plan) }}" class="diet-btn diet-btn--soft">
                                <i class="fas fa-utensils" aria-hidden="true"></i>
                                Armar plan
                            </a>

                            <a href="{{ route('diet-plans.edit', $plan) }}"
                               class="diet-icon-btn"
                               title="Editar datos del plan"
                               aria-label="Editar {{ $plan->name }}">
                                <i class="far fa-edit" aria-hidden="true"></i>
                            </a>

                            <form method="POST"
                                  action="{{ route('diet-plans.destroy', $plan) }}"
                                  data-confirm="¿Eliminar el plan «{{ $plan->name }}» de {{ $patientName }}?">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="diet-icon-btn diet-icon-btn--danger"
                                        title="Eliminar plan"
                                        aria-label="Eliminar {{ $plan->name }}"
                                        data-loading-text="">
                                    <i class="far fa-trash-alt" aria-hidden="true"></i>
                                </button>
                            </form>
                        </div>

                    </article>
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
    @include('partials.theme-styles', ['section' => 'admin'])
@stop


{{-- =========================================================
     SCRIPTS
     ========================================================= --}}
@section('js')
    @vite(['resources/js/pages/diet-plans.js'])
@stop
