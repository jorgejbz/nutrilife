@extends('adminlte::page')

@section('title', 'Planes alimenticios | NutriLife')

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

        <h1>Planes alimenticios</h1>

        <p>
            Diseña, administra y consulta los planes
            nutricionales de tus pacientes.
        </p>
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
<div class="consultations-page dp">

    {{-- BANNER DE BIENVENIDA --}}
    <section class="dp-welcome-banner" aria-label="Bienvenida al módulo de planes alimenticios">
        <div class="dp-welcome-icon" aria-hidden="true">
            <i class="fas fa-utensils"></i>
        </div>

        <div class="dp-welcome-copy">
            <span class="dp-welcome-eyebrow">NUTRICIÓN PERSONALIZADA</span>

            <h2>
                Alimenta sus metas,
                <strong>transforma su bienestar.</strong>
            </h2>

            <p>
                Diseña y organiza planes alimenticios personalizados
                para acompañar el progreso de cada paciente.
            </p>
        </div>

        <div class="dp-welcome-art" aria-hidden="true">
            <i class="fas fa-leaf dp-art-leaf dp-art-leaf-one"></i>
            <i class="fas fa-seedling dp-art-seedling"></i>
            <i class="fas fa-leaf dp-art-leaf dp-art-leaf-two"></i>
        </div>
    </section>

    {{-- MENSAJES --}}
    @if(session('success'))
        <div class="consultation-alert" role="status">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="consultation-alert" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- MÉTRICAS (todos los planes del nutriólogo, sin filtros) --}}
    <div class="consultation-stats">
        <div class="consultation-stat">
            <div class="consultation-stat-icon">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div>
                <span>Total de planes</span>
                <h3>{{ $totalPlans }}</h3>
            </div>
        </div>

        <div class="consultation-stat">
            <div class="consultation-stat-icon today">
                <i class="fas fa-seedling"></i>
            </div>
            <div>
                <span>Planes activos</span>
                <h3>{{ $activePlans }}</h3>
            </div>
        </div>

        <div class="consultation-stat">
            <div class="consultation-stat-icon patients">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <span>Planes finalizados</span>
                <h3>{{ $totalPlans - $activePlans }}</h3>
            </div>
        </div>
    </div>

    {{-- HISTORIAL DE PLANES --}}
    <div class="consultations-card">

        <div class="consultations-card-header dp-list-header">
            <div>
                <span>PLANES NUTRICIONALES</span>
                <h4>Planes alimenticios registrados</h4>
            </div>

            @if($totalPlans > 0)
                {{-- Filtros (GET): búsqueda, paciente y estado --}}
                <form method="GET" action="{{ route('diet-plans.index') }}" class="dp-filters" data-auto-submit>
                    <div class="dp-filter-field">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <label class="visually-hidden" for="filterSearch">Buscar plan</label>
                        <input type="search" name="q" id="filterSearch" value="{{ $search }}" placeholder="Buscar plan, objetivo o paciente…" maxlength="100">
                    </div>

                    <div class="dp-filter-field">
                        <i class="far fa-user" aria-hidden="true"></i>
                        <label class="visually-hidden" for="filterPatient">Paciente</label>
                        <select name="paciente" id="filterPatient">
                            <option value="">Todos los pacientes</option>
                            @foreach($patients as $patient)
                                <option value="{{ $patient->id }}" @selected($patientId === $patient->id)>
                                    {{ $patient->first_name }} {{ $patient->last_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="dp-filter-chips" role="group" aria-label="Estado del plan">
                        @foreach(['' => 'Todos', 'activos' => 'Activos', 'finalizados' => 'Finalizados'] as $value => $label)
                            <label class="{{ ($status ?? '') === $value ? 'is-selected' : '' }}">
                                <input type="radio" name="estado" value="{{ $value }}" @checked(($status ?? '') === $value)>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>

                    <noscript><button type="submit" class="dp-btn">Filtrar</button></noscript>
                </form>
            @else
                <i class="fas fa-utensils"></i>
            @endif
        </div>

        @if($totalPlans === 0)

            {{-- SIN PLANES --}}
            <div class="empty-consultations">
                <i class="fas fa-utensils"></i>
                <h5>Sin planes alimenticios</h5>
                <p>
                    Cuando diseñes un plan nutricional para alguno
                    de tus pacientes, aparecerá registrado aquí.
                </p>
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

            {{-- LISTA DE PLANES --}}
            <div class="consultations-list">
                @foreach($plans as $plan)
                    @php
                        $planKcal = $plan->totals()['kcal'];
                        $planTarget = (float) $plan->target_kcal;
                        $planPct = $planTarget > 0 ? $planKcal / $planTarget * 100 : 0;
                        $mealCount = $plan->meals->count();
                    @endphp

                    <div class="consultation-item dp-plan-item">

                        {{-- FECHA --}}
                        <div class="consultation-date" aria-hidden="true">
                            <strong>{{ $plan->starts_at->format('d') }}</strong>
                            <span>{{ mb_strtoupper($plan->starts_at->locale('es')->translatedFormat('M')) }}</span>
                        </div>

                        {{-- INFORMACIÓN --}}
                        <div class="consultation-info">
                            <div class="dp-patient-heading">
                                <h5>
                                    @if($plan->patient)
                                        {{ $plan->patient->first_name }} {{ $plan->patient->last_name }}
                                    @else
                                        Paciente eliminado
                                    @endif
                                </h5>

                                <span class="dp-plan-status {{ $plan->active ? 'is-active' : 'is-finished' }}">
                                    <i class="fas fa-circle"></i>
                                    {{ $plan->statusLabel() }}
                                </span>
                            </div>

                            <p>{{ $plan->name }}</p>

                            @if($plan->goal)
                                <p class="dp-plan-goal"><i class="fas fa-bullseye" aria-hidden="true"></i> {{ $plan->goal }}</p>
                            @endif

                            {{-- OBJETIVOS DIARIOS --}}
                            <div class="dp-plan-macros">
                                <span><i class="fas fa-fire"></i> {{ number_format($planTarget, 0) }} kcal</span>
                                <span><i class="fas fa-dumbbell"></i> {{ number_format((float) $plan->target_protein_g, 0) }} g proteína</span>
                                <span><i class="fas fa-bread-slice"></i> {{ number_format((float) $plan->target_carbs_g, 0) }} g carbohidratos</span>
                                <span><i class="fas fa-tint"></i> {{ number_format((float) $plan->target_lipids_g, 0) }} g grasas</span>
                            </div>

                            {{-- AVANCE: kcal ya armadas en las comidas vs. objetivo --}}
                            <div class="dp-plan-progress" title="Calorías armadas en las comidas contra el objetivo">
                                <div class="dp-progress-track is-small" aria-hidden="true">
                                    <div class="dp-progress-value {{ $planPct > 105 ? 'is-over' : '' }}" style="width: {{ min($planPct, 100) }}%"></div>
                                </div>
                                <span>
                                    {{ number_format($planKcal, 0) }} de {{ number_format($planTarget, 0) }} kcal
                                    · {{ $mealCount }} {{ $mealCount === 1 ? 'comida' : 'comidas' }}
                                </span>
                            </div>

                            {{-- FECHAS --}}
                            <div class="consultation-meta">
                                <span>
                                    <i class="far fa-calendar"></i>
                                    Inicio: {{ $plan->starts_at->format('d/m/Y') }}
                                </span>
                                <span>
                                    <i class="far fa-calendar-check"></i>
                                    Fin: {{ $plan->ends_at ? $plan->ends_at->format('d/m/Y') : 'Actualidad' }}
                                </span>
                            </div>
                        </div>

                        {{-- ACCIONES --}}
                        <div class="dp-plan-actions">
                            <a href="{{ route('diet-plans.show', $plan) }}" class="dp-plan-view">
                                <i class="fas fa-eye"></i>
                                Ver / editar plan
                            </a>

                            {{-- diet_plans tiene deleted_at: se archiva (soft delete) --}}
                            <form action="{{ route('diet-plans.destroy', $plan) }}"
                                  method="POST"
                                  data-confirm="¿Eliminar el plan «{{ $plan->name }}»? Dejará de aparecer en tu lista."
                                  data-confirm-title="Eliminar plan alimenticio"
                                  data-confirm-accept="Eliminar plan"
                                  data-confirm-variant="danger"
                                  data-confirm-icon="fa-trash-alt">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="dp-plan-delete" data-loading-text="Eliminando…">
                                    <i class="fas fa-trash-alt"></i>
                                    Eliminar plan
                                </button>
                            </form>
                        </div>

                    </div>
                @endforeach
            </div>

        @endif
    </div>
</div>
@stop

{{-- =========================================================
     ESTILOS / SCRIPTS
     ========================================================= --}}
@section('css')
    @include('diet-plans.partials.styles')
@stop

@section('js')
    @vite(['resources/js/pages/diet-plans.js'])
@stop
