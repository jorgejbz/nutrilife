@extends('adminlte::page')

<<<<<<< HEAD
@section('title', $plan->name.' | NutriLife')

@php
    $patient = $plan->patient;
    $patientName = $patient ? $patient->first_name.' '.$patient->last_name : 'Paciente eliminado';
    $totals = $plan->totals();
    $targets = $plan->targets();
    $progress = $plan->progress();
    $remaining = $targets['kcal'] - $totals['kcal'];

    $macroRows = [
        'protein_g' => ['label' => 'Proteínas', 'short' => 'P', 'class' => 'is-protein'],
        'carbs_g' => ['label' => 'Carbohidratos', 'short' => 'C', 'class' => 'is-carbs'],
        'lipids_g' => ['label' => 'Grasas', 'short' => 'G', 'class' => 'is-lipids'],
    ];

    $severityLabels = ['leve' => 'Leve', 'moderada' => 'Moderada', 'severa' => 'Severa'];

    $fmt = fn (float $value, int $decimals = 0) => number_format($value, $decimals);
    $trimNumber = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    $oneDecimal = fn ($value) => rtrim(rtrim(number_format((float) $value, 1, '.', ''), '0'), '.');

    // Catálogo para el buscador (lo usa diet-plans.js)
    $catalog = $foods->map(fn ($food) => [
        'id' => $food->id,
        'name' => $food->name,
        'group_id' => $food->food_group_id,
        'group' => $food->foodGroup?->name,
        'portion_grams' => (float) $food->portion_grams,
        'portion' => $food->portion_description,
        'kcal' => (float) $food->kcal,
        'protein' => (float) $food->protein_g,
        'carbs' => (float) $food->carbs_g,
        'lipids' => (float) $food->lipids_g,
        'warnings' => $warnings[$food->id] ?? [],
    ])->values();
@endphp


{{-- =========================================================
     CONTENT HEADER
     ========================================================= --}}
@section('content_header')

    <div class="consultations-header diet-show-header">
        <div>
            <span class="consultation-eyebrow">
                <i class="fas fa-leaf mr-1"></i>
                PLAN ALIMENTICIO
            </span>

            <h1>{{ $plan->name }}</h1>

            <p class="diet-show-meta">
                <span><i class="far fa-user" aria-hidden="true"></i> {{ $patientName }}</span>
                <span class="diet-status {{ $plan->active ? 'is-active' : 'is-finished' }}">{{ $plan->statusLabel() }}</span>
                <span>
                    <i class="far fa-calendar" aria-hidden="true"></i>
                    {{ $plan->starts_at->format('d/m/Y') }}
                    — {{ $plan->ends_at ? $plan->ends_at->format('d/m/Y') : 'sin fecha de fin' }}
                </span>
            </p>
        </div>

        <div class="diet-header-actions">
            <a href="{{ route('diet-plans.index') }}" class="diet-btn diet-btn--ghost">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                Planes
            </a>
            <button type="button" class="diet-btn diet-btn--soft" data-open-dialog="duplicateDialog">
                <i class="far fa-copy" aria-hidden="true"></i>
                Duplicar / asignar
            </button>
            <a href="{{ route('diet-plans.edit', $plan) }}" class="btn-consultation-primary">
                <i class="far fa-edit" aria-hidden="true"></i>
                Editar datos
            </a>
        </div>
    </div>

@stop


{{-- =========================================================
     CONTENT
     ========================================================= --}}
@section('content')

<div class="consultations-page diet-page diet-builder" id="dietBuilder">

    @if(session('success'))
        <div class="consultation-alert" role="status">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="diet-form-alert" role="alert">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <div>
                <strong>No se guardó el cambio</strong>
                <span>{{ $errors->first() }}</span>
            </div>
        </div>
    @endif

    @unless($plan->active)
        <div class="diet-note" role="note">
            <i class="fas fa-flag-checkered" aria-hidden="true"></i>
            Este plan está finalizado. Puedes consultarlo y ajustarlo; para reactivarlo usa «Editar datos».
        </div>
    @endunless

    {{-- =====================================================
         RESUMEN DEL DÍA + CONTEXTO DEL PACIENTE
         ===================================================== --}}
    <div class="diet-top-grid">

        <section class="consultations-card diet-summary" aria-labelledby="daySummaryTitle">
            <div class="diet-summary-head">
                <div>
                    <span class="diet-kicker">RESUMEN DEL DÍA</span>
                    <h2 id="daySummaryTitle" class="visually-hidden">Resumen del día</h2>
                    <div class="diet-kcal">
                        <strong>{{ $fmt($totals['kcal']) }}</strong>
                        <span>/ {{ $fmt($targets['kcal']) }} kcal</span>
                    </div>
                </div>
                <span class="diet-percent {{ $progress['kcal'] > 105 ? 'is-over' : '' }}">{{ $fmt($progress['kcal']) }}%</span>
            </div>

            <div class="diet-bar {{ $progress['kcal'] > 105 ? 'is-over' : '' }}"
                 role="progressbar"
                 aria-label="Calorías del día"
                 aria-valuemin="0"
                 aria-valuemax="100"
                 aria-valuenow="{{ (int) min($progress['kcal'], 100) }}">
                <span style="width: {{ min($progress['kcal'], 100) }}%"></span>
            </div>

            <p class="diet-remaining">
                @if($targets['kcal'] <= 0)
                    Define las calorías objetivo en «Editar datos».
                @elseif($remaining >= 0)
                    Faltan <strong>{{ $fmt($remaining) }} kcal</strong> para el objetivo.
                @else
                    Supera el objetivo por <strong>{{ $fmt(abs($remaining)) }} kcal</strong>.
                @endif
            </p>

            <div class="diet-macro-grid">
                @foreach($macroRows as $key => $meta)
                    <div class="diet-macro {{ $meta['class'] }}">
                        <div class="diet-macro-top">
                            <span class="diet-macro-badge" aria-hidden="true">{{ $meta['short'] }}</span>
                            <span>{{ $meta['label'] }}</span>
                            <small>{{ $fmt($progress[$key]) }}%</small>
                        </div>
                        <strong>{{ $fmt($totals[$key], 1) }} <small>/ {{ $fmt($targets[$key]) }} g</small></strong>
                        <div class="diet-bar diet-bar--sm {{ $progress[$key] > 110 ? 'is-over' : '' }}">
                            <span style="width: {{ min($progress[$key], 100) }}%"></span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <aside class="consultations-card diet-patient-card" aria-labelledby="patientCardTitle">
            <div class="diet-patient-head">
                <span class="diet-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($patient?->first_name ?? 'P', 0, 1)) }}</span>
                <div>
                    <span class="diet-kicker">PACIENTE</span>
                    <h2 id="patientCardTitle">{{ $patientName }}</h2>
                </div>
            </div>

            @if($patient?->goal)
                <p class="diet-patient-goal"><i class="fas fa-bullseye" aria-hidden="true"></i> {{ $patient->goal }}</p>
            @endif

            <div class="diet-patient-block">
                <span>Alergias e intolerancias</span>
                @forelse($patient?->allergens ?? [] as $allergen)
                    <span class="diet-tag diet-tag--allergen">
                        {{ $allergen->name }}
                        @if($allergen->pivot->severity)
                            · {{ $severityLabels[$allergen->pivot->severity] ?? $allergen->pivot->severity }}
                        @endif
                    </span>
                @empty
                    <small class="diet-muted">Sin alergias registradas.</small>
                @endforelse
            </div>

            <div class="diet-patient-block">
                <span>Patologías</span>
                @forelse($patient?->pathologies ?? [] as $pathology)
                    <span class="diet-tag">{{ $pathology->name }}</span>
                @empty
                    <small class="diet-muted">Sin patologías registradas.</small>
                @endforelse
            </div>

            {{-- Última evaluación y consulta (solo consulta; no se calcula nada) --}}
            <div class="diet-patient-block diet-patient-clinical">
                <span>Última evaluación</span>
                @if($latestEvaluation)
                    <dl class="diet-clinical-grid">
                        <div><dt>Peso</dt><dd>{{ $latestEvaluation->weight !== null ? number_format((float) $latestEvaluation->weight, 1).' kg' : '—' }}</dd></div>
                        <div><dt>Estatura</dt><dd>{{ $latestEvaluation->height !== null ? number_format((float) $latestEvaluation->height, 0).' cm' : '—' }}</dd></div>
                        <div><dt>IMC</dt><dd>{{ $latestEvaluation->bmi !== null ? number_format((float) $latestEvaluation->bmi, 1) : '—' }}</dd></div>
                        <div><dt>Grasa</dt><dd>{{ $latestEvaluation->body_fat !== null ? number_format((float) $latestEvaluation->body_fat, 1).' %' : '—' }}</dd></div>
                    </dl>
                    <small class="diet-muted">Del {{ $latestEvaluation->evaluation_date->format('d/m/Y') }}</small>
                @else
                    <small class="diet-muted">Sin evaluaciones registradas.</small>
                @endif
            </div>

            @if($lastConsultation?->recommendations || $lastConsultation?->observations)
                <div class="diet-patient-block">
                    <span>Última consulta · {{ $lastConsultation->consultation_at->format('d/m/Y') }}</span>
                    <p class="diet-last-consultation">{{ \Illuminate\Support\Str::limit($lastConsultation->recommendations ?: $lastConsultation->observations, 180) }}</p>
                </div>
            @endif

            @if($patient && ! $patient->trashed())
                <a href="{{ route('pacientes.show', $patient) }}" class="diet-link">
                    Ver expediente <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            @endif
        </aside>
    </div>

    {{-- =====================================================
         OBJETIVO E INDICACIONES DEL PLAN
         ===================================================== --}}
    @if($plan->goal || $plan->instructions)
        <section class="consultations-card diet-instructions" aria-labelledby="instructionsTitle">
            <div class="diet-instructions-head">
                <span class="diet-meal-icon" aria-hidden="true"><i class="far fa-clipboard"></i></span>
                <div>
                    <span class="diet-kicker">INDICACIONES DEL NUTRIÓLOGO</span>
                    <h2 id="instructionsTitle">{{ $plan->goal ?: 'Indicaciones del plan' }}</h2>
                </div>
                <a href="{{ route('diet-plans.edit', $plan) }}#instructions" class="diet-icon-btn" title="Editar indicaciones" aria-label="Editar indicaciones">
                    <i class="far fa-edit" aria-hidden="true"></i>
                </a>
            </div>
            @if($plan->instructions)
                <ul class="diet-instructions-list">
                    @foreach(preg_split('/\R+/', trim($plan->instructions)) as $line)
                        @if(trim($line) !== '')
                            <li>{{ trim($line) }}</li>
                        @endif
                    @endforeach
                </ul>
            @endif
        </section>
    @else
        <p class="diet-note">
            <i class="far fa-clipboard" aria-hidden="true"></i>
            Sin objetivo ni indicaciones. <a href="{{ route('diet-plans.edit', $plan) }}#goal" class="diet-link">Agregarlos</a>
        </p>
    @endif

    {{-- =====================================================
         COMIDAS
         ===================================================== --}}
    <section class="diet-meals" aria-labelledby="mealsTitle">

        <div class="diet-section-head">
            <div>
                <span class="diet-kicker">DISTRIBUCIÓN DEL DÍA</span>
                <h2 id="mealsTitle">Comidas</h2>
            </div>

            <form method="POST" action="{{ route('diet-plans.meals.store', $plan) }}" class="diet-add-meal">
                @csrf
                <label for="meal_name" class="visually-hidden">Nombre del tiempo de comida</label>
                <input type="text"
                       name="meal_name"
                       id="meal_name"
                       list="mealSuggestions"
                       maxlength="100"
                       value="{{ old('meal_name') }}"
                       placeholder="Ej. Desayuno, Colación, Cena…"
                       required>
                <datalist id="mealSuggestions">
                    @foreach($mealSuggestions as $suggestion)
                        <option value="{{ $suggestion }}">
                    @endforeach
                </datalist>
                <label for="meal_time" class="visually-hidden">Horario</label>
                <input type="time"
                       name="meal_time"
                       id="meal_time"
                       class="diet-add-meal__time"
                       value="{{ old('meal_time') }}"
                       title="Horario (opcional)">
                <button type="submit" class="diet-btn diet-btn--primary" data-loading-text="Agregando…">
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    Agregar comida
                </button>
            </form>
        </div>

        @if($foods->isEmpty())
            <div class="diet-note diet-note--warning" role="note">
                <i class="fas fa-database" aria-hidden="true"></i>
                El catálogo de alimentos (tabla <code>foods</code>) está vacío. Cárgalo para poder agregar alimentos a las comidas.
            </div>
        @endif

        @if($plan->meals->isEmpty())

            <div class="consultations-card empty-consultations diet-empty">
                <i class="fas fa-mug-hot"></i>
                <h5>Aún no hay comidas en este plan</h5>
                <p>Agrega los tiempos de comida del día (por ejemplo Desayuno, Comida y Cena) y después sus alimentos.</p>
            </div>

        @else

            <div class="diet-meal-list">
                @foreach($plan->meals as $meal)
                    @php $mealTotals = $meal->totals(); @endphp

                    <article class="consultations-card diet-meal" id="comida-{{ $meal->id }}" aria-labelledby="meal-title-{{ $meal->id }}">

                        <header class="diet-meal-head">
                            <div class="diet-meal-title">
                                <span class="diet-meal-icon" aria-hidden="true"><i class="{{ $meal->icon() }}"></i></span>
                                <div>
                                    <span class="diet-kicker">
                                        COMIDA {{ $loop->iteration }}
                                        @if($meal->timeLabel())
                                            · <i class="far fa-clock" aria-hidden="true"></i> {{ $meal->timeLabel() }}
                                        @endif
                                    </span>
                                    <h3 id="meal-title-{{ $meal->id }}">{{ $meal->meal_name }}</h3>
                                    @if($meal->notes)
                                        <p class="diet-meal-notes"><i class="fas fa-info-circle" aria-hidden="true"></i> {{ $meal->notes }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="diet-meal-totals">
                                <strong>{{ $fmt($mealTotals['kcal']) }} <small>kcal</small></strong>
                                <span>P {{ $fmt($mealTotals['protein_g'], 1) }} · C {{ $fmt($mealTotals['carbs_g'], 1) }} · G {{ $fmt($mealTotals['lipids_g'], 1) }}</span>
                            </div>

                            <button type="button"
                                    class="diet-icon-btn"
                                    title="Editar nombre, horario e indicación"
                                    aria-label="Editar {{ $meal->meal_name }}"
                                    aria-expanded="false"
                                    data-toggle-meal-edit="mealEdit{{ $meal->id }}">
                                <i class="far fa-edit" aria-hidden="true"></i>
                            </button>

                            <form method="POST"
                                  action="{{ route('diet-plans.meals.destroy', [$plan, $meal]) }}"
                                  data-confirm="¿Eliminar «{{ $meal->meal_name }}» y sus {{ $meal->items->count() }} alimentos?">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="diet-icon-btn diet-icon-btn--danger"
                                        title="Eliminar comida"
                                        aria-label="Eliminar {{ $meal->meal_name }}"
                                        data-loading-text="">
                                    <i class="far fa-trash-alt" aria-hidden="true"></i>
                                </button>
                            </form>
                        </header>

                        {{-- Editar comida (PATCH diet-plans.meals.update) --}}
                        <form method="POST"
                              action="{{ route('diet-plans.meals.update', [$plan, $meal]) }}"
                              class="diet-meal-edit"
                              id="mealEdit{{ $meal->id }}"
                              hidden>
                            @csrf
                            @method('PATCH')
                            <div class="diet-meal-edit__grid">
                                <label>
                                    <span>Nombre</span>
                                    <input type="text" name="meal_name" value="{{ $meal->meal_name }}" maxlength="100" list="mealSuggestions" required>
                                </label>
                                <label>
                                    <span>Horario</span>
                                    <input type="time" name="meal_time" value="{{ $meal->timeLabel() }}">
                                </label>
                                <label class="diet-meal-edit__wide">
                                    <span>Indicación de esta comida</span>
                                    <input type="text" name="notes" value="{{ $meal->notes }}" maxlength="255" placeholder="Ej. Sin sal añadida; preparar al vapor">
                                </label>
                            </div>
                            <div class="diet-meal-edit__actions">
                                <button type="button" class="diet-btn diet-btn--ghost" data-toggle-meal-edit="mealEdit{{ $meal->id }}">Cancelar</button>
                                <button type="submit" class="diet-btn diet-btn--primary">Guardar</button>
                            </div>
                        </form>

                        @if($meal->items->isEmpty())
                            <p class="diet-meal-empty">Sin alimentos todavía.</p>
                        @else
                            <ul class="diet-items">
                                @foreach($meal->items as $item)
                                    @php
                                        $nutrients = $item->nutrients();
                                        $itemWarnings = $item->food_id ? ($warnings[$item->food_id] ?? []) : [];
                                    @endphp

                                    <li class="diet-item">
                                        <div class="diet-item-main">
                                            <strong>{{ $item->food?->name ?? 'Alimento sin catálogo' }}</strong>
                                            <span class="diet-item-sub">
                                                {{ $item->food?->foodGroup?->name ?? 'Sin grupo' }}
                                                · {{ $trimNumber($item->equivalents) }} {{ (float) $item->equivalents === 1.0 ? 'porción' : 'porciones' }}
                                                @if($item->food?->portion_description)
                                                    de {{ $item->food->portion_description }}
                                                @endif
                                            </span>

                                            @foreach($itemWarnings as $warning)
                                                <span class="diet-warning diet-warning--{{ $warning['type'] }}">
                                                    <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                                                    {{ $warning['text'] }}
                                                </span>
                                            @endforeach
                                        </div>

                                        {{-- Cantidad editable (PATCH) --}}
                                        <form method="POST"
                                              action="{{ route('diet-plans.items.update', [$plan, $meal, $item]) }}"
                                              class="diet-grams-form">
                                            @csrf
                                            @method('PATCH')
                                            <label for="grams-{{ $item->id }}" class="visually-hidden">Gramos de {{ $item->food?->name }}</label>
                                            <input type="number"
                                                   id="grams-{{ $item->id }}"
                                                   name="grams"
                                                   value="{{ $trimNumber($item->grams) }}"
                                                   data-original="{{ $trimNumber($item->grams) }}"
                                                   min="0.1"
                                                   max="{{ \App\Models\DietPlanItem::MAX_GRAMS }}"
                                                   step="0.1"
                                                   inputmode="decimal"
                                                   required>
                                            <span aria-hidden="true">g</span>
                                            <button type="submit" class="diet-grams-save" title="Guardar cantidad" aria-label="Guardar cantidad" data-loading-text="">
                                                <i class="fas fa-check" aria-hidden="true"></i>
                                            </button>
                                        </form>

                                        <div class="diet-item-values">
                                            <strong>{{ $fmt($nutrients['kcal']) }} kcal</strong>
                                            <span>P {{ $fmt($nutrients['protein_g'], 1) }} · C {{ $fmt($nutrients['carbs_g'], 1) }} · G {{ $fmt($nutrients['lipids_g'], 1) }}</span>
                                        </div>

                                        <form method="POST"
                                              action="{{ route('diet-plans.items.destroy', [$plan, $meal, $item]) }}"
                                              data-confirm="¿Quitar {{ $item->food?->name ?? 'este alimento' }} de {{ $meal->meal_name }}?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="diet-icon-btn diet-icon-btn--danger diet-icon-btn--sm"
                                                    title="Quitar alimento"
                                                    aria-label="Quitar {{ $item->food?->name ?? 'alimento' }}"
                                                    data-loading-text="">
                                                <i class="fas fa-times" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <footer class="diet-meal-foot">
                            <button type="button"
                                    class="diet-btn diet-btn--soft"
                                    data-open-food-dialog
                                    data-meal-id="{{ $meal->id }}"
                                    data-meal-name="{{ $meal->meal_name }}"
                                    data-action="{{ route('diet-plans.items.store', [$plan, $meal]) }}"
                                    @disabled($foods->isEmpty())>
                                <i class="fas fa-plus" aria-hidden="true"></i>
                                Agregar alimento
                            </button>
                        </footer>
                    </article>
                @endforeach
            </div>

        @endif
    </section>

</div>


{{-- =========================================================
     DIÁLOGO: AGREGAR ALIMENTO DEL CATÁLOGO
     ========================================================= --}}
<dialog class="diet-dialog diet-dialog--catalog" id="foodDialog" aria-labelledby="foodDialogTitle">
    <form method="POST" action="" id="foodDialogForm" class="diet-dialog-inner">
        @csrf
        {{-- Solo para reabrir el diálogo si la validación falla --}}
        <input type="hidden" name="dialog_meal" id="dialogMeal" value="{{ old('dialog_meal') }}">

        <header class="diet-dialog-head">
            <div>
                <span class="diet-kicker">CATÁLOGO DE ALIMENTOS</span>
                <h2 id="foodDialogTitle">Agregar a <span data-meal-label>la comida</span></h2>
            </div>
            <button type="button" class="diet-icon-btn" data-close-dialog aria-label="Cerrar">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </header>

        <div class="diet-dialog-search">
            <div class="diet-search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <label for="foodSearch" class="visually-hidden">Buscar alimento</label>
                <input type="search" id="foodSearch" placeholder="Buscar alimento…" autocomplete="off" aria-describedby="foodResultCount">
            </div>

            @if($foodGroups->count() > 1)
                {{-- Pantallas anchas: chips que se acomodan en varias líneas (antes
                     era una fila con scroll horizontal que cortaba los grupos). --}}
                <div class="diet-filter-chips diet-filter-chips--wrap" role="group" aria-label="Grupo de alimentos">
                    <button type="button" class="diet-chip is-selected" data-group="" aria-pressed="true">Todos</button>
                    @foreach($foodGroups as $group)
                        <button type="button" class="diet-chip" data-group="{{ $group->id }}" aria-pressed="false">{{ $group->name }}</button>
                    @endforeach
                </div>

                {{-- Celular: el mismo filtro como lista desplegable --}}
                <div class="diet-filter-select diet-group-select">
                    <i class="fas fa-layer-group" aria-hidden="true"></i>
                    <label for="foodGroupSelect" class="visually-hidden">Grupo de alimentos</label>
                    <select id="foodGroupSelect">
                        <option value="">Todos los grupos</option>
                        @foreach($foodGroups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="diet-dialog-status">
                <span id="foodResultCount" aria-live="polite">{{ $catalog->count() }} {{ $catalog->count() === 1 ? 'alimento' : 'alimentos' }}</span>
                <button type="button" class="diet-link-btn" id="foodClearFilters" hidden>
                    <i class="fas fa-eraser" aria-hidden="true"></i>
                    Limpiar búsqueda
                </button>
                <a href="{{ route('foods.index') }}" class="diet-link-btn" target="_blank" rel="noopener">
                    Ver catálogo completo
                    <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                    <span class="visually-hidden">(se abre en otra pestaña)</span>
                </a>
            </div>
        </div>

        <div class="diet-food-options" id="foodOptions" role="radiogroup" aria-label="Alimentos" tabindex="-1">
            @foreach($catalog as $food)
                <label class="diet-food-option"
                       data-food-option
                       data-name="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($food['name'].' '.($food['group'] ?? ''))) }}"
                       data-group="{{ $food['group_id'] }}">
                    <input type="radio" name="food_id" value="{{ $food['id'] }}" @checked((string) old('food_id') === (string) $food['id']) required>
                    <span class="diet-food-text">
                        <strong>{{ $food['name'] }}</strong>
                        <small>
                            {{ $food['group'] ?? 'Sin grupo' }} · porción {{ $food['portion'] ? $food['portion'].' ('.$trimNumber($food['portion_grams']).' g)' : $trimNumber($food['portion_grams']).' g' }}
                        </small>
                        <span class="diet-food-nutrients">
                            <b>{{ $fmt($food['kcal']) }} kcal</b>
                            <span>P {{ $oneDecimal($food['protein']) }} g</span>
                            <span>C {{ $oneDecimal($food['carbs']) }} g</span>
                            <span>G {{ $oneDecimal($food['lipids']) }} g</span>
                        </span>
                        @foreach($food['warnings'] as $warning)
                            <span class="diet-warning diet-warning--{{ $warning['type'] }}">
                                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                                {{ $warning['text'] }}
                            </span>
                        @endforeach
                    </span>
                </label>
            @endforeach
            <div class="diet-no-results" id="foodNoResults" hidden>
                <i class="fas fa-search" aria-hidden="true"></i>
                <p>Ningún alimento coincide con la búsqueda.</p>
                <button type="button" class="diet-btn diet-btn--ghost" data-clear-food-filters>Limpiar búsqueda</button>
            </div>
        </div>

        <footer class="diet-dialog-foot">
            <div class="diet-grams-field">
                <label for="dialogGrams">Cantidad</label>
                <div class="diet-grams-input">
                    <input type="number"
                           name="grams"
                           id="dialogGrams"
                           value="{{ old('grams') }}"
                           min="0.1"
                           max="{{ \App\Models\DietPlanItem::MAX_GRAMS }}"
                           step="0.1"
                           inputmode="decimal"
                           required>
                    <span aria-hidden="true">g</span>
                </div>
            </div>

            <p class="diet-preview" id="foodPreview" aria-live="polite">Elige un alimento para ver su aporte.</p>

            <div class="diet-dialog-actions">
                <button type="button" class="diet-btn diet-btn--ghost" data-close-dialog>Cancelar</button>
                <button type="submit" class="diet-btn diet-btn--primary" data-loading-text="Agregando…">
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    Agregar
                </button>
            </div>
        </footer>
    </form>
</dialog>

{{-- =========================================================
     DIÁLOGO: DUPLICAR / ASIGNAR A OTRO PACIENTE
     ========================================================= --}}
<dialog class="diet-dialog diet-dialog--sm" id="duplicateDialog" aria-labelledby="duplicateTitle"
        @if(old('_dialog') === 'duplicate') data-reopen @endif>
    <form method="POST" action="{{ route('diet-plans.duplicate', $plan) }}" class="diet-dialog-inner">
        @csrf
        <input type="hidden" name="_dialog" value="duplicate">
        <header class="diet-dialog-head">
            <div>
                <span class="diet-kicker">REUTILIZAR ESTRUCTURA</span>
                <h2 id="duplicateTitle">Duplicar este plan</h2>
            </div>
            <button type="button" class="diet-icon-btn" data-close-dialog aria-label="Cerrar">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </header>

        <div class="diet-dialog-body">
            <p class="diet-muted">Se copian los objetivos, las indicaciones, las comidas y sus alimentos. El plan actual no cambia.</p>

            <label class="diet-field">
                <span>Paciente</span>
                <select name="patient_id" required>
                    @foreach($patients as $option)
                        <option value="{{ $option->id }}" @selected((int) old('patient_id', $plan->patient_id) === $option->id)>
                            {{ $option->first_name }} {{ $option->last_name }}{{ $option->id === $plan->patient_id ? ' (mismo paciente)' : '' }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="diet-field">
                <span>Nombre del nuevo plan</span>
                <input type="text" name="name" maxlength="255" required value="{{ old('name', \Illuminate\Support\Str::limit($plan->name.' (copia)', 255, '')) }}">
            </label>

            <label class="diet-field">
                <span>Inicio</span>
                <input type="date" name="starts_at" required min="2000-01-01" max="2100-12-31" value="{{ old('starts_at', now()->format('Y-m-d')) }}">
            </label>

            @if($plan->active)
                <label class="diet-check">
                    <input type="checkbox" name="finish_original" value="1">
                    Si es para el mismo paciente, finalizar el plan actual
                </label>
            @endif

            @if($patients->isEmpty())
                <p class="diet-form-alert">No tienes pacientes activos para asignar el plan.</p>
            @endif
        </div>

        <footer class="diet-dialog-foot diet-dialog-foot--simple">
            <div class="diet-dialog-actions">
                <button type="button" class="diet-btn diet-btn--ghost" data-close-dialog>Cancelar</button>
                <button type="submit" class="diet-btn diet-btn--primary" data-loading-text="Duplicando…" @disabled($patients->isEmpty())>
                    <i class="far fa-copy" aria-hidden="true"></i>
                    Crear copia
                </button>
            </div>
        </footer>
    </form>
</dialog>

<script type="application/json" id="foodCatalogData">@json($catalog)</script>

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
=======
@section('title', $plan->name . ' | NutriLife')


{{-- =========================================================
     HEADER
========================================================= --}}

@section('content_header')

<div class="builder-header">

    <div>

        <a
            href="{{ route('diet-plans.index') }}"
            class="builder-breadcrumb"
        >
            <i class="fas fa-arrow-left"></i>
            Planes alimenticios
        </a>

        <span class="builder-eyebrow">
            <i class="fas fa-leaf"></i>
            CONSTRUCTOR NUTRICIONAL
        </span>

        <h1>
            {{ $plan->name }}
        </h1>

        <p>
            {{ $plan->patient->first_name }}
            {{ $plan->patient->last_name }}
        </p>

    </div>


    <div class="header-actions">

        <span class="plan-status {{ $plan->active ? 'active' : '' }}">

            <span class="status-dot"></span>

            {{ $plan->active ? 'Plan activo' : 'Plan finalizado' }}

        </span>

    </div>

</div>

@stop



{{-- =========================================================
     CONTENT
========================================================= --}}

@section('content')

<div class="diet-builder">


    {{-- =====================================================
         CÁLCULOS GENERALES DEL DÍA
    ====================================================== --}}

    @php

        $dayKcal = 0;
        $dayProtein = 0;
        $dayCarbs = 0;
        $dayFat = 0;

        foreach ($plan->meals as $dayMeal) {

            foreach ($dayMeal->items as $dayItem) {

                // Alimento del catálogo
                if ($dayItem->food) {

                    $portionGrams =
                        (float) $dayItem->food->portion_grams;

                    if ($portionGrams <= 0) {
                        continue;
                    }

                    $factor =
                        (float) $dayItem->grams
                        / $portionGrams;

                    $dayKcal +=
                        (float) $dayItem->food->kcal
                        * $factor;

                    $dayProtein +=
                        (float) $dayItem->food->protein_g
                        * $factor;

                    $dayCarbs +=
                        (float) $dayItem->food->carbs_g
                        * $factor;

                    $dayFat +=
                        (float) $dayItem->food->lipids_g
                        * $factor;

                // Alimento agregado manualmente
                } else {

                    $dayKcal += (float) ($dayItem->custom_kcal ?? 0);
                    $dayProtein += (float) ($dayItem->custom_protein_g ?? 0);
                    $dayCarbs += (float) ($dayItem->custom_carbs_g ?? 0);
                    $dayFat += (float) ($dayItem->custom_lipids_g ?? 0);
                }
            }
        }


        $targetKcal =
            (float) $plan->target_kcal;

        $targetProtein =
            (float) $plan->target_protein_g;

        $targetCarbs =
            (float) $plan->target_carbs_g;

        $targetFat =
            (float) $plan->target_lipids_g;


        $kcalPercentage =
            $targetKcal > 0
                ? ($dayKcal / $targetKcal) * 100
                : 0;

        $proteinPercentage =
            $targetProtein > 0
                ? ($dayProtein / $targetProtein) * 100
                : 0;

        $carbsPercentage =
            $targetCarbs > 0
                ? ($dayCarbs / $targetCarbs) * 100
                : 0;

        $fatPercentage =
            $targetFat > 0
                ? ($dayFat / $targetFat) * 100
                : 0;


        $remainingKcal =
            $targetKcal - $dayKcal;

    @endphp



    {{-- =====================================================
         ALERTAS
    ====================================================== --}}

    @if(session('success'))

        <div class="builder-alert success">

            <div class="builder-alert-icon">
                <i class="fas fa-check"></i>
            </div>

            <div>

                <strong>¡Listo!</strong>

                <span>
                    {{ session('success') }}
                </span>

            </div>

        </div>

    @endif


    @if($errors->any())

        <div class="builder-alert error">

            <div class="builder-alert-icon">
                <i class="fas fa-exclamation"></i>
            </div>

            <div>

                <strong>Revisa la información</strong>

                <span>
                    {{ $errors->first() }}
                </span>

            </div>

        </div>

    @endif



    {{-- =====================================================
         RESUMEN DEL DÍA
    ====================================================== --}}

    <section class="daily-summary">


        {{-- CALORÍAS --}}

        <div class="daily-main">

            <div class="daily-main-top">

                <div>

                    <span class="summary-label">
                        CALORÍAS DEL DÍA
                    </span>

                    <div class="daily-kcal">

                        <strong>
                            {{ number_format($dayKcal, 0) }}
                        </strong>

                        <span>
                            / {{ number_format($targetKcal, 0) }} kcal
                        </span>

                    </div>

                </div>


                <div
                    class="daily-percentage
                    {{ $kcalPercentage > 105 ? 'over' : '' }}"
                >
                    {{ number_format($kcalPercentage, 0) }}%
                </div>

            </div>


            <div class="progress-track">

                <div
                    class="progress-value {{ $kcalPercentage > 105 ? 'over' : '' }}"
                    style="width: {{ min($kcalPercentage, 100) }}%"
                ></div>

            </div>


            <div class="daily-remaining">

                @if($remainingKcal >= 0)

                    Faltan

                    <strong>
                        {{ number_format($remainingKcal, 0) }} kcal
                    </strong>

                    para alcanzar el objetivo.

                @else

                    El plan supera el objetivo por

                    <strong>
                        {{ number_format(abs($remainingKcal), 0) }} kcal
                    </strong>.

                @endif

            </div>

        </div>



        {{-- MACROS --}}

        <div class="macro-progress-grid">


            {{-- PROTEÍNA --}}

            <div class="macro-progress-card">

                <div class="macro-card-top">

                    <div class="macro-symbol protein">
                        P
                    </div>

                    <span>
                        PROTEÍNA
                    </span>

                </div>


                <div class="macro-current">

                    <strong>
                        {{ number_format($dayProtein, 1) }}g
                    </strong>

                    <small>
                        de {{ number_format($targetProtein, 0) }}g
                    </small>

                </div>


                <div class="progress-track small">

                    <div
                        class="progress-value"
                        style="width: {{ min($proteinPercentage, 100) }}%"
                    ></div>

                </div>


                <div class="macro-percent">
                    {{ number_format($proteinPercentage, 0) }}%
                </div>

            </div>



            {{-- CARBOHIDRATOS --}}

            <div class="macro-progress-card">

                <div class="macro-card-top">

                    <div class="macro-symbol carbs">
                        C
                    </div>

                    <span>
                        CARBOHIDRATOS
                    </span>

                </div>


                <div class="macro-current">

                    <strong>
                        {{ number_format($dayCarbs, 1) }}g
                    </strong>

                    <small>
                        de {{ number_format($targetCarbs, 0) }}g
                    </small>

                </div>


                <div class="progress-track small">

                    <div
                        class="progress-value"
                        style="width: {{ min($carbsPercentage, 100) }}%"
                    ></div>

                </div>


                <div class="macro-percent">
                    {{ number_format($carbsPercentage, 0) }}%
                </div>

            </div>



            {{-- GRASAS --}}

            <div class="macro-progress-card">

                <div class="macro-card-top">

                    <div class="macro-symbol fats">
                        G
                    </div>

                    <span>
                        GRASAS
                    </span>

                </div>


                <div class="macro-current">

                    <strong>
                        {{ number_format($dayFat, 1) }}g
                    </strong>

                    <small>
                        de {{ number_format($targetFat, 0) }}g
                    </small>

                </div>


                <div class="progress-track small">

                    <div
                        class="progress-value"
                        style="width: {{ min($fatPercentage, 100) }}%"
                    ></div>

                </div>


                <div class="macro-percent">
                    {{ number_format($fatPercentage, 0) }}%
                </div>

            </div>


        </div>

    </section>



    {{-- =====================================================
         HEADER COMIDAS
    ====================================================== --}}

    <section class="meals-section">


        <div class="meals-heading">

            <div>

                <span class="section-eyebrow">
                    <i class="fas fa-utensils"></i>
                    DISTRIBUCIÓN DEL DÍA
                </span>

                <h2>
                    Comidas
                </h2>

                <p>
                    Construye el plan agregando alimentos a cada tiempo de comida.
                </p>

            </div>


            <button
                type="button"
                class="add-meal-button"
                onclick="toggleMealForm()"
            >

                <i class="fas fa-plus"></i>

                Agregar comida

            </button>

        </div>



        {{-- =================================================
             FORMULARIO NUEVA COMIDA
        ================================================== --}}

        <div
            class="new-meal-form"
            id="mealForm"
        >

            <form
                action="{{ route('diet-plans.meals.store', $plan) }}"
                method="POST"
            >

                @csrf


                <div class="meal-form-field">

                    <label>
                        Nombre de la comida
                    </label>

                    <input
                        type="text"
                        name="meal_name"
                        placeholder="Ej. Desayuno, Colación, Cena..."
                        required
                    >

                </div>


                <div class="meal-form-actions">

                    <button
                        type="button"
                        class="secondary-button"
                        onclick="toggleMealForm()"
                    >
                        Cancelar
                    </button>


                    <button
                        type="submit"
                        class="primary-button"
                    >

                        <i class="fas fa-plus"></i>

                        Crear comida

                    </button>

                </div>

            </form>

        </div>



        {{-- =================================================
             SIN COMIDAS
        ================================================== --}}

        @if($plan->meals->isEmpty())

            <div class="no-meals">

                <div class="no-meals-icon">

                    <i class="fas fa-bowl-food"></i>

                </div>


                <span>
                    COMIENZA EL PLAN
                </span>


                <h3>
                    Aún no has creado comidas.
                </h3>


                <p>
                    Agrega desayuno, colaciones, comida, cena
                    o cualquier otro tiempo que necesite el paciente.
                </p>


                <button
                    type="button"
                    class="primary-button"
                    onclick="toggleMealForm()"
                >

                    <i class="fas fa-plus"></i>

                    Agregar primera comida

                </button>

            </div>



        @else


            {{-- =================================================
                 GRID DE COMIDAS
            ================================================== --}}

            <div class="meals-list">


                @foreach($plan->meals as $meal)

                    @php

                        $mealKcal = 0;
                        $mealProtein = 0;
                        $mealCarbs = 0;
                        $mealFat = 0;

                        foreach ($meal->items as $mealItem) {

                            // Alimento del catálogo
                            if ($mealItem->food) {

                                $portionGrams =
                                    (float) $mealItem->food->portion_grams;

                                if ($portionGrams <= 0) {
                                    continue;
                                }

                                $factor =
                                    (float) $mealItem->grams
                                    / $portionGrams;

                                $mealKcal +=
                                    (float) $mealItem->food->kcal
                                    * $factor;

                                $mealProtein +=
                                    (float) $mealItem->food->protein_g
                                    * $factor;

                                $mealCarbs +=
                                    (float) $mealItem->food->carbs_g
                                    * $factor;

                                $mealFat +=
                                    (float) $mealItem->food->lipids_g
                                    * $factor;

                            // Alimento agregado manualmente
                            } else {

                                $mealKcal += (float) ($mealItem->custom_kcal ?? 0);
                                $mealProtein += (float) ($mealItem->custom_protein_g ?? 0);
                                $mealCarbs += (float) ($mealItem->custom_carbs_g ?? 0);
                                $mealFat += (float) ($mealItem->custom_lipids_g ?? 0);
                            }
                        }

                    @endphp



                    <article class="meal-card">


                        {{-- =====================================
                             HEADER COMIDA
                        ====================================== --}}

                        <div class="meal-header">


                            <div class="meal-title">

                                <div class="meal-icon">

                                    @if(
                                        str_contains(
                                            strtolower($meal->meal_name),
                                            'desay'
                                        )
                                    )

                                        <i class="fas fa-mug-hot"></i>

                                    @elseif(
                                        str_contains(
                                            strtolower($meal->meal_name),
                                            'cena'
                                        )
                                    )

                                        <i class="fas fa-moon"></i>

                                    @elseif(
                                        str_contains(
                                            strtolower($meal->meal_name),
                                            'colaci'
                                        )
                                    )

                                        <i class="fas fa-apple-whole"></i>

                                    @else

                                        <i class="fas fa-bowl-food"></i>

                                    @endif

                                </div>


                                <div>

                                    <span>
                                        COMIDA {{ $meal->meal_order }}
                                    </span>

                                    <h3>
                                        {{ $meal->meal_name }}
                                    </h3>

                                </div>

                            </div>



                            <div class="meal-header-right">

                                <div class="meal-header-kcal">

                                    <strong>
                                        {{ number_format($mealKcal, 0) }}
                                    </strong>

                                    <span>
                                        kcal
                                    </span>

                                </div>


                                {{-- BOTÓN PLATILLO COMPLETO (NUEVO) --}}
                                <button
                                    type="button"
                                    class="open-dish-button"
                                    onclick="openDishDrawer({{ $meal->id }})"
                                    title="Insertar una receta completa"
                                >
                                    <i class="fas fa-utensils"></i>
                                    Platillo
                                </button>


                                {{-- BOTÓN ALIMENTO INDIVIDUAL --}}
                                <button
                                    type="button"
                                    class="open-food-button"
                                    onclick="openFoodDrawer({{ $meal->id }})"
                                >

                                    <i class="fas fa-plus"></i>

                                    Alimento

                                </button>


                                <form
                                    action="{{ route(
                                        'diet-plans.meals.destroy',
                                        [$plan, $meal]
                                    ) }}"
                                    method="POST"
                                    onsubmit="
                                        return confirm(
                                            '¿Eliminar {{ $meal->meal_name }}? También se eliminarán sus alimentos.'
                                        );
                                    "
                                >

                                    @csrf
                                    @method('DELETE')


                                    <button
                                        type="submit"
                                        class="delete-meal-button"
                                        title="Eliminar comida"
                                    >

                                        <i class="far fa-trash-alt"></i>

                                    </button>

                                </form>

                            </div>

                        </div>


                        {{-- =====================================
                             ALIMENTOS
                        ====================================== --}}

                        @if($meal->items->isEmpty())

                            <div class="meal-empty">

                                <div class="meal-empty-icon">
                                    <i class="fas fa-carrot"></i>
                                </div>

                                <strong>
                                    Sin alimentos
                                </strong>

                                <span>
                                    Agrega el primer alimento de esta comida.
                                </span>


                                <button
                                    type="button"
                                    onclick="openFoodDrawer({{ $meal->id }})"
                                >

                                    <i class="fas fa-plus"></i>

                                    Agregar alimento

                                </button>

                            </div>



                        @else


                            <div class="food-list">


                                @foreach($meal->items as $item)

                                    @php

                                        if ($item->food) {

                                            $foodPortion =
                                                (float) $item->food->portion_grams;

                                            $itemFactor =
                                                $foodPortion > 0
                                                    ? (float) $item->grams
                                                        / $foodPortion
                                                    : 0;

                                            $itemKcal =
                                                (float) $item->food->kcal
                                                * $itemFactor;

                                            $itemProtein =
                                                (float) $item->food->protein_g
                                                * $itemFactor;

                                            $itemCarbs =
                                                (float) $item->food->carbs_g
                                                * $itemFactor;

                                            $itemFat =
                                                (float) $item->food->lipids_g
                                                * $itemFactor;

                                            $itemName =
                                                $item->food->name;

                                            $itemPortionDescription =
                                                $item->food->portion_description;

                                        } else {

                                            $itemKcal =
                                                (float) ($item->custom_kcal ?? 0);

                                            $itemProtein =
                                                (float) ($item->custom_protein_g ?? 0);

                                            $itemCarbs =
                                                (float) ($item->custom_carbs_g ?? 0);

                                            $itemFat =
                                                (float) ($item->custom_lipids_g ?? 0);

                                            $itemName =
                                                $item->custom_name
                                                ?? 'Alimento personalizado';

                                            $itemPortionDescription =
                                                $item->custom_portion_description
                                                ?? null;
                                        }

                                    @endphp


                                    <div class="food-row">


                                        <div class="food-row-main">


                                            <div class="food-row-icon">
                                                <i class="fas fa-apple-whole"></i>
                                            </div>


                                            <div class="food-info">

                                                <strong>
                                                    {{ $itemName }}
                                                </strong>


                                                <span>

                                                    {{ number_format(
                                                        (float) $item->grams,
                                                        0
                                                    ) }} g

                                                    @if($itemPortionDescription)

                                                        ·

                                                        {{ $itemPortionDescription }}

                                                    @endif

                                                </span>

                                            </div>

                                        </div>



                                        <div class="food-row-values">

                                            <strong>
                                                {{ number_format($itemKcal, 0) }}
                                                kcal
                                            </strong>


                                            <span>

                                                {{ number_format($itemProtein, 1) }}P

                                                <b>·</b>

                                                {{ number_format($itemCarbs, 1) }}C

                                                <b>·</b>

                                                {{ number_format($itemFat, 1) }}G

                                            </span>

                                        </div>



                                        <form
                                            action="{{ route(
                                                'diet-plans.items.destroy',
                                                [$plan, $meal, $item]
                                            ) }}"
                                            method="POST"
                                            onsubmit="
                                                return confirm(
                                                    '¿Eliminar este alimento?'
                                                );
                                            "
                                        >

                                            @csrf
                                            @method('DELETE')


                                            <button
                                                type="submit"
                                                class="delete-food-button"
                                                title="Eliminar alimento"
                                            >

                                                <i class="far fa-trash-alt"></i>

                                            </button>

                                        </form>


                                    </div>

                                @endforeach


                            </div>

                        @endif



                        {{-- =====================================
                             FOOTER / TOTAL
                        ====================================== --}}

                        <div class="meal-footer">


                            <div>

                                <span>
                                    TOTAL
                                </span>

                                <strong>
                                    {{ strtoupper($meal->meal_name) }}
                                </strong>

                            </div>


                            <div class="meal-footer-values">

                                <strong>
                                    {{ number_format($mealKcal, 0) }}
                                    kcal
                                </strong>

                                <span>

                                    {{ number_format($mealProtein, 1) }}P

                                    ·

                                    {{ number_format($mealCarbs, 1) }}C

                                    ·

                                    {{ number_format($mealFat, 1) }}G

                                </span>

                            </div>

                        </div>


                    </article>



                    {{-- =================================================
                         DRAWER AGREGAR PLATILLO (NUEVO)
                    ================================================== --}}

                    <div
                        class="food-drawer-overlay"
                        id="dishDrawerOverlay{{ $meal->id }}"
                        onclick="closeDishDrawer({{ $meal->id }}, event)"
                    >

                        <aside
                            class="food-drawer"
                            id="dishDrawer{{ $meal->id }}"
                        >

                            <div class="drawer-header">

                                <div>

                                    <span>
                                        INSERTAR RECETA COMPLETA
                                    </span>

                                    <h3>
                                        Platillos para {{ $meal->meal_name }}
                                    </h3>

                                </div>


                                <button
                                    type="button"
                                    onclick="forceCloseDishDrawer({{ $meal->id }})"
                                >

                                    <i class="fas fa-times"></i>

                                </button>

                            </div>


                            <form
                                action="{{ route(
                                    'diet-plans.meals.dishes.store',
                                    [$plan, $meal]
                                ) }}"
                                method="POST"
                                class="drawer-form"
                            >

                                @csrf


                                <div class="drawer-field">

                                    <label>
                                        Selecciona un Platillo / Receta
                                    </label>


                                    <select
                                        name="dish_id"
                                        class="food-select"
                                        required
                                    >

                                        <option value="">
                                            Seleccionar platillo...
                                        </option>

                                        @foreach($dishes ?? [] as $dish)

                                            <option value="{{ $dish->id }}">
                                                {{ $dish->name ?? ('Platillo #' . $dish->id) }}
                                            </option>

                                        @endforeach

                                    </select>

                                </div>


                                <div class="custom-warning">

                                    <i class="fas fa-circle-info"></i>

                                    <span>
                                        Se insertarán todos los ingredientes desglosados de este platillo dentro de {{ $meal->meal_name }}.
                                    </span>

                                </div>


                                <button
                                    type="submit"
                                    class="drawer-submit"
                                >

                                    <i class="fas fa-utensils"></i>

                                    Insertar ingredientes del platillo

                                </button>

                            </form>

                        </aside>

                    </div>



                    {{-- =================================================
                         DRAWER AGREGAR ALIMENTO
                    ================================================== --}}

                    <div
                        class="food-drawer-overlay"
                        id="drawerOverlay{{ $meal->id }}"
                        onclick="closeFoodDrawer({{ $meal->id }}, event)"
                    >


                        <aside
                            class="food-drawer"
                            id="foodDrawer{{ $meal->id }}"
                        >


                            <div class="drawer-header">

                                <div>

                                    <span>
                                        AGREGAR ALIMENTO
                                    </span>

                                    <h3>
                                        {{ $meal->meal_name }}
                                    </h3>

                                </div>


                                <button
                                    type="button"
                                    onclick="forceCloseFoodDrawer({{ $meal->id }})"
                                >

                                    <i class="fas fa-times"></i>

                                </button>

                            </div>



                            <form
                                action="{{ route(
                                    'diet-plans.items.store',
                                    [$plan, $meal]
                                ) }}"
                                method="POST"
                                class="drawer-form"
                            >

                                @csrf


                                {{-- BUSCAR --}}

                                <div class="drawer-field">

                                    <label>
                                        Buscar alimento
                                    </label>


                                    <div class="drawer-search">

                                        <i class="fas fa-search"></i>


                                        <input
                                            type="text"
                                            class="food-search"
                                            placeholder="Ej. pollo, arroz, avena..."
                                            data-target="foodSelect{{ $meal->id }}"
                                        >

                                    </div>

                                </div>



                                {{-- SELECT --}}

                                <div class="drawer-field">

                                    <label>
                                        Selecciona un alimento
                                    </label>


                                    <select
                                        name="food_id"
                                        id="foodSelect{{ $meal->id }}"
                                        class="food-select"
                                        required
                                    >

                                        <option value="">
                                            Seleccionar alimento...
                                        </option>

                                        @foreach($foods as $food)

                                            <option
                                                value="{{ $food->id }}"
                                                data-name="{{ strtolower($food->name) }}"
                                                data-kcal="{{ $food->kcal }}"
                                                data-protein="{{ $food->protein_g }}"
                                                data-carbs="{{ $food->carbs_g }}"
                                                data-fat="{{ $food->lipids_g }}"
                                                data-portion="{{ $food->portion_grams }}"
                                                data-portion-description="{{ $food->portion_description ?? '1 porción' }}"
                                            >
                                                {{ $food->name }}
                                            </option>

                                        @endforeach

                                    </select>

                                </div>



                                {{-- FORMA DE MEDIR --}}

                                <div class="drawer-field">

                                    <label>
                                        Forma de medir
                                    </label>


                                    <div class="measure-selector">

                                        <button
                                            type="button"
                                            class="measure-option active"
                                            id="measurePortion{{ $meal->id }}"
                                            onclick="setMeasureMode({{ $meal->id }}, 'portion')"
                                        >

                                            <i class="fas fa-egg"></i>

                                            Porciones

                                        </button>


                                        <button
                                            type="button"
                                            class="measure-option"
                                            id="measureGrams{{ $meal->id }}"
                                            onclick="setMeasureMode({{ $meal->id }}, 'grams')"
                                        >

                                            <i class="fas fa-weight-scale"></i>

                                            Gramos

                                        </button>

                                    </div>

                                </div>



                                {{-- CANTIDAD --}}

                                <div class="drawer-field">

                                    <label>
                                        Cantidad
                                    </label>


                                    <div class="quantity-control">


                                        <button
                                            type="button"
                                            onclick="changeQuantity({{ $meal->id }}, -1)"
                                        >
                                            <i class="fas fa-minus"></i>
                                        </button>



                                        <div class="quantity-input-wrapper">

                                            <input
                                                type="number"
                                                id="quantity{{ $meal->id }}"
                                                value="1"
                                                min="0.5"
                                                step="0.5"
                                                oninput="updateFoodPreview({{ $meal->id }})"
                                            >

                                            <span
                                                id="quantityUnit{{ $meal->id }}"
                                            >
                                                porciones
                                            </span>

                                        </div>



                                        <button
                                            type="button"
                                            onclick="changeQuantity({{ $meal->id }}, 1)"
                                        >
                                            <i class="fas fa-plus"></i>
                                        </button>


                                    </div>



                                    <div
                                        class="portion-equivalence"
                                        id="portionEquivalence{{ $meal->id }}"
                                    >
                                        Selecciona un alimento.
                                    </div>



                                    <input
                                        type="hidden"
                                        name="grams"
                                        id="grams{{ $meal->id }}"
                                        value="100"
                                    >

                                    <input
                                        type="hidden"
                                        id="measureMode{{ $meal->id }}"
                                        value="portion"
                                    >

                                </div>



                                {{-- PREVIEW --}}

                                <div
                                    class="food-preview"
                                    id="foodPreview{{ $meal->id }}"
                                >

                                    <div class="preview-placeholder">

                                        <i class="fas fa-apple-whole"></i>

                                        <span>
                                            Selecciona un alimento para ver
                                            sus valores nutricionales.
                                        </span>

                                    </div>

                                </div>



                                {{-- SUBMIT --}}

                                <button
                                    type="submit"
                                    class="drawer-submit"
                                >

                                    <i class="fas fa-plus"></i>

                                    Agregar a {{ $meal->meal_name }}

                                </button>


                            </form>


                            {{-- CAMBIAR A ALIMENTO MANUAL --}}

                            <div class="custom-food-divider">

                                <span>
                                    ¿No encuentras el alimento?
                                </span>

                                <button
                                    type="button"
                                    onclick="showCustomFoodForm({{ $meal->id }})"
                                >
                                    <i class="fas fa-pen"></i>

                                    Agregar manualmente
                                </button>

                            </div>



                            {{-- FORMULARIO ALIMENTO MANUAL --}}

                            <form
                                action="{{ route(
                                    'diet-plans.items.custom.store',
                                    [$plan, $meal]
                                ) }}"
                                method="POST"
                                class="custom-food-form"
                                id="customFoodForm{{ $meal->id }}"
                            >

                                @csrf


                                <div class="custom-form-header">

                                    <button
                                        type="button"
                                        onclick="hideCustomFoodForm({{ $meal->id }})"
                                    >
                                        <i class="fas fa-arrow-left"></i>
                                    </button>


                                    <div>

                                        <span>
                                            ALIMENTO PERSONALIZADO
                                        </span>

                                        <h4>
                                            Agregar a {{ $meal->meal_name }}
                                        </h4>

                                    </div>

                                </div>



                                <div class="custom-warning">

                                    <i class="fas fa-circle-info"></i>

                                    <span>
                                        Este alimento solamente se guardará dentro
                                        de este plan y no será añadido al catálogo
                                        general de NutriLife.
                                    </span>

                                </div>



                                <div class="drawer-field">

                                    <label>
                                        Nombre del alimento
                                    </label>

                                    <input
                                        type="text"
                                        name="custom_name"
                                        class="custom-input"
                                        placeholder="Ej. Salsa casera de aguacate"
                                        maxlength="150"
                                        required
                                    >

                                </div>



                                <div class="drawer-field">

                                    <label>
                                        Cantidad utilizada
                                    </label>

                                    <div class="custom-unit-row">

                                        <input
                                            type="number"
                                            name="grams"
                                            class="custom-input"
                                            placeholder="150"
                                            min="0.01"
                                            step="0.01"
                                            required
                                        >

                                        <span>
                                            g
                                        </span>

                                    </div>

                                </div>



                                <div class="drawer-field">

                                    <label>
                                        Descripción de la porción
                                        <small>(opcional)</small>
                                    </label>

                                    <input
                                        type="text"
                                        name="custom_portion_description"
                                        class="custom-input"
                                        placeholder="Ej. 1 plato, 1 taza, 2 piezas..."
                                        maxlength="150"
                                    >

                                </div>



                                <div class="drawer-field">

                                    <label>
                                        Calorías de esa cantidad
                                    </label>

                                    <div class="custom-unit-row">

                                        <input
                                            type="number"
                                            name="custom_kcal"
                                            class="custom-input"
                                            placeholder="320"
                                            min="0"
                                            step="0.01"
                                            required
                                        >

                                        <span>
                                            kcal
                                        </span>

                                    </div>

                                </div>



                                <div class="custom-macro-grid">


                                    <div>

                                        <label>
                                            Proteína
                                        </label>

                                        <div class="custom-unit-row">

                                            <input
                                                type="number"
                                                name="custom_protein_g"
                                                class="custom-input"
                                                placeholder="25"
                                                min="0"
                                                step="0.01"
                                                required
                                            >

                                            <span>g</span>

                                        </div>

                                    </div>



                                    <div>

                                        <label>
                                            Carbohidratos
                                        </label>

                                        <div class="custom-unit-row">

                                            <input
                                                type="number"
                                                name="custom_carbs_g"
                                                class="custom-input"
                                                placeholder="30"
                                                min="0"
                                                step="0.01"
                                                required
                                            >

                                            <span>g</span>

                                        </div>

                                    </div>



                                    <div>

                                        <label>
                                            Grasas
                                        </label>

                                        <div class="custom-unit-row">

                                            <input
                                                type="number"
                                                name="custom_lipids_g"
                                                class="custom-input"
                                                placeholder="12"
                                                min="0"
                                                step="0.01"
                                                required
                                            >

                                            <span>g</span>

                                        </div>

                                    </div>


                                </div>



                                <button
                                    type="submit"
                                    class="drawer-submit"
                                >

                                    <i class="fas fa-plus"></i>

                                    Agregar alimento personalizado

                                </button>

                            </form>

                        </aside>

                    </div>


                @endforeach


            </div>

        @endif


    </section>



</div>

@stop



{{-- =========================================================
     CSS
========================================================= --}}

@section('css')

@include('partials.theme-styles', ['section' => 'admin'])

<link
    rel="stylesheet"
    href="{{ asset('css/nutriadmin.css') }}"
>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>


<style>

/* ESTILO DEL BOTÓN PLATILLO */
.open-dish-button {
    height: 36px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 0 11px;
    border: 1px solid rgba(220, 233, 108, 0.25);
    border-radius: 10px;
    background: rgba(220, 233, 108, 0.08);
    color: #e2e99a;
    font-size: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: .2s ease;
}

.open-dish-button:hover {
    background: rgba(220, 233, 108, 0.18);
    color: #ffffff;
}

:root {

    --nl-bg: #07140e;
    --nl-card: rgba(255,255,255,.045);
    --nl-border: rgba(255,255,255,.085);

    --nl-green: #a8ce9e;
    --nl-green-soft: #8db88d;

    --nl-lime: #dce96c;

    --nl-text: #f1f8f2;
    --nl-muted: rgba(218,234,221,.43);

}


body {

    background:

        radial-gradient(
            circle at 12% 12%,
            rgba(65,123,80,.19),
            transparent 32%
        ),

        radial-gradient(
            circle at 90% 20%,
            rgba(126,169,110,.10),
            transparent 30%
        ),

        linear-gradient(
            135deg,
            #06130d,
            #0b2015 52%,
            #07160f
        )

        !important;

    background-attachment:
        fixed !important;

}


.content-wrapper,
.content-header {

    background:
        transparent !important;

}


.content {

    padding-bottom:
        60px !important;

}


.main-header.navbar {

    background:
        rgba(8,25,17,.96) !important;

    border-bottom:
        1px solid rgba(255,255,255,.055) !important;

}


.main-header .nav-link {

    color:
        rgba(231,242,233,.65) !important;

}


.builder-header {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        25px;

    padding:
        8px 0 14px;

}


.builder-breadcrumb {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        7px;

    margin-bottom:
        12px;

    color:
        rgba(203,224,207,.38) !important;

    font-size:
        9px;

    text-decoration:
        none !important;

}


.builder-breadcrumb:hover {

    color:
        #b5d5af !important;

}


.builder-eyebrow {

    display:
        flex;

    align-items:
        center;

    gap:
        7px;

    color:
        #8faf91;

    font-size:
        8px;

    font-weight:
        700;

    letter-spacing:
        1.8px;

}


.builder-header h1 {

    margin:
        6px 0 3px;

    color:
        var(--nl-text);

    font-size:
        clamp(27px, 3vw, 34px);

    font-weight:
        700;

}


.builder-header p {

    margin:
        0;

    color:
        var(--nl-muted);

    font-size:
        11px;

}


.plan-status {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        7px;

    padding:
        8px 12px;

    border:
        1px solid rgba(255,255,255,.07);

    border-radius:
        100px;

    background:
        rgba(255,255,255,.035);

    color:
        rgba(221,235,223,.42);

    font-size:
        9px;

}


.plan-status.active {

    border-color:
        rgba(118,196,128,.12);

    background:
        rgba(87,162,98,.08);

    color:
        #a7d1ab;

}


.status-dot {

    width:
        6px;

    height:
        6px;

    border-radius:
        50%;

    background:
        rgba(220,235,223,.35);

}


.plan-status.active .status-dot {

    background:
        #7dc98a;

    box-shadow:
        0 0 8px rgba(125,201,138,.5);

}


.builder-alert {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

    margin-bottom:
        20px;

    padding:
        14px 17px;

    border-radius:
        15px;

}


.builder-alert.success {

    border:
        1px solid rgba(121,197,129,.16);

    background:
        rgba(75,153,88,.09);

    color:
        #bee1bf;

}


.builder-alert.error {

    border:
        1px solid rgba(222,94,94,.16);

    background:
        rgba(177,67,67,.08);

    color:
        #efb1b1;

}


.builder-alert-icon {

    width:
        34px;

    height:
        34px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    flex-shrink:
        0;

    border-radius:
        11px;

    background:
        rgba(255,255,255,.055);

}


.builder-alert strong,
.builder-alert span {

    display:
        block;

}


.builder-alert strong {

    font-size:
        10px;

}


.builder-alert span {

    margin-top:
        2px;

    opacity:
        .65;

    font-size:
        9px;

}


.daily-summary {

    display:
        grid;

    grid-template-columns:
        .9fr 1.5fr;

    gap:
        25px;

    margin-bottom:
        38px;

    padding:
        26px;

    border:
        1px solid var(--nl-border);

    border-radius:
        24px;

    background:

        linear-gradient(
            145deg,
            rgba(255,255,255,.06),
            rgba(255,255,255,.018)
        );

    backdrop-filter:
        blur(24px);

    box-shadow:
        0 20px 50px rgba(0,0,0,.08);

}


.daily-main {

    padding-right:
        25px;

    border-right:
        1px solid rgba(255,255,255,.065);

}


.daily-main-top {

    display:
        flex;

    align-items:
        flex-start;

    justify-content:
        space-between;

    gap:
        15px;

}


.summary-label {

    color:
        rgba(203,225,208,.33);

    font-size:
        7px;

    font-weight:
        700;

    letter-spacing:
        1.5px;

}


.daily-kcal {

    display:
        flex;

    align-items:
        baseline;

    gap:
        7px;

    margin-top:
        7px;

}


.daily-kcal strong {

    color:
        #f1f8f2;

    font-size:
        34px;

    font-weight:
        700;

}


.daily-kcal span {

    color:
        rgba(218,233,221,.37);

    font-size:
        9px;

}


.daily-percentage {

    padding:
        7px 10px;

    border-radius:
        100px;

    background:
        rgba(171,211,162,.10);

    color:
        #bad9b4;

    font-size:
        10px;

    font-weight:
        700;

}


.daily-percentage.over {

    background:
        rgba(218,105,90,.10);

    color:
        #e8a49a;

}


.progress-track {

    width:
        100%;

    height:
        8px;

    overflow:
        hidden;

    margin-top:
        18px;

    border-radius:
        100px;

    background:
        rgba(255,255,255,.055);

}


.progress-track.small {

    height:
        5px;

    margin-top:
        12px;

}


.progress-value {

    height:
        100%;

    border-radius:
        inherit;

    background:

        linear-gradient(
            90deg,
            #91c18e,
            #dce872
        );

    transition:
        width .4s ease;

}


.progress-value.over {

    background:

        linear-gradient(
            90deg,
            #d98973,
            #edb06f
        );

}


.daily-remaining {

    margin-top:
        10px;

    color:
        rgba(214,231,217,.37);

    font-size:
        8px;

}


.daily-remaining strong {

    color:
        #b8d5b2;

}


.macro-progress-grid {

    display:
        grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap:
        12px;

}


.macro-progress-card {

    position:
        relative;

    padding:
        17px;

    border:
        1px solid rgba(255,255,255,.055);

    border-radius:
        17px;

    background:
        rgba(255,255,255,.027);

}


.macro-card-top {

    display:
        flex;

    align-items:
        center;

    gap:
        8px;

}


.macro-card-top > span {

    color:
        rgba(207,227,211,.32);

    font-size:
        6px;

    font-weight:
        700;

    letter-spacing:
        1px;

}


.macro-symbol {

    width:
        27px;

    height:
        27px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        9px;

    font-size:
        8px;

    font-weight:
        800;

}


.macro-symbol.protein {

    background:
        rgba(127,190,139,.10);

    color:
        #9bc6a3;

}


.macro-symbol.carbs {

    background:
        rgba(205,213,112,.09);

    color:
        #d1da80;

}


.macro-symbol.fats {

    background:
        rgba(110,172,190,.09);

    color:
        #8eb9c6;

}


.macro-current {

    margin-top:
        12px;

}


.macro-current strong {

    display:
        block;

    color:
        #e9f4eb;

    font-size:
        16px;

}


.macro-current small {

    display:
        block;

    margin-top:
        2px;

    color:
        rgba(211,229,214,.30);

    font-size:
        7px;

}


.macro-percent {

    margin-top:
        7px;

    color:
        rgba(212,230,215,.32);

    font-size:
        7px;

}


.meals-section {

    margin-top:
        5px;

}


.meals-heading {

    display:
        flex;

    align-items:
        flex-end;

    justify-content:
        space-between;

    gap:
        20px;

    margin-bottom:
        18px;

}


.section-eyebrow {

    display:
        flex;

    align-items:
        center;

    gap:
        7px;

    color:
        #8eaf92;

    font-size:
        7px;

    font-weight:
        700;

    letter-spacing:
        1.5px;

}


.meals-heading h2 {

    margin:
        5px 0 3px;

    color:
        #eef7f0;

    font-size:
        23px;

}


.meals-heading p {

    margin:
        0;

    color:
        rgba(213,231,216,.37);

    font-size:
        9px;

}


.add-meal-button,
.primary-button {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        7px;

    min-height:
        40px;

    padding:
        0 15px;

    border:
        0;

    border-radius:
        11px;

    background:

        linear-gradient(
            135deg,
            #afd1a4,
            #dce873
        );

    color:
        #102417;

    font-size:
        9px;

    font-weight:
        700;

    cursor:
        pointer;

    transition:
        .2s ease;

}


.add-meal-button:hover,
.primary-button:hover {

    transform:
        translateY(-2px);

}


.secondary-button {

    min-height:
        40px;

    padding:
        0 15px;

    border:
        1px solid rgba(255,255,255,.08);

    border-radius:
        11px;

    background:
        rgba(255,255,255,.03);

    color:
        rgba(226,238,228,.50);

    font-size:
        9px;

    cursor:
        pointer;

}


.new-meal-form {

    display:
        none;

    margin-bottom:
        18px;

    padding:
        18px;

    border:
        1px solid rgba(255,255,255,.075);

    border-radius:
        16px;

    background:
        rgba(255,255,255,.03);

}


.new-meal-form.active {

    display:
        block;

}


.new-meal-form form {

    display:
        flex;

    align-items:
        flex-end;

    gap:
        15px;

}


.meal-form-field {

    flex:
        1;

}


.meal-form-field label {

    display:
        block;

    margin-bottom:
        7px;

    color:
        rgba(218,234,221,.48);

    font-size:
        8px;

}


.meal-form-field input {

    width:
        100%;

    height:
        42px;

    padding:
        0 13px;

    border:
        1px solid rgba(255,255,255,.09);

    border-radius:
        11px;

    outline:
        none;

    background:
        rgba(255,255,255,.04);

    color:
        white;

}


.meal-form-actions {

    display:
        flex;

    gap:
        8px;

}


.meals-list {

    display:
        grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap:
        18px;

    align-items:
        start;

}


.meal-card {

    overflow:
        hidden;

    border:
        1px solid rgba(255,255,255,.085);

    border-radius:
        20px;

    background:

        linear-gradient(
            145deg,
            rgba(255,255,255,.052),
            rgba(255,255,255,.018)
        );

    backdrop-filter:
        blur(20px);

    transition:
        .22s ease;

}


.meal-card:hover {

    border-color:
        rgba(164,205,157,.16);

    transform:
        translateY(-2px);

}


.meal-header {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        12px;

    padding:
        17px;

    border-bottom:
        1px solid rgba(255,255,255,.055);

}


.meal-title {

    display:
        flex;

    align-items:
        center;

    gap:
        11px;

    min-width:
        0;

}


.meal-icon {

    width:
        42px;

    height:
        42px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    flex-shrink:
        0;

    border-radius:
        13px;

    background:
        rgba(151,195,145,.10);

    color:
        #a8cc9f;

}


.meal-title span {

    display:
        block;

    color:
        rgba(205,225,209,.28);

    font-size:
        6px;

    font-weight:
        700;

    letter-spacing:
        1.2px;

}


.meal-title h3 {

    margin:
        3px 0 0;

    overflow:
        hidden;

    color:
        #edf7ef;

    font-size:
        15px;

    font-weight:
        600;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;

}


.meal-header-right {

    display:
        flex;

    align-items:
        center;

    gap:
        7px;

}


.meal-header-right form {

    margin:
        0;

}


.meal-header-kcal {

    margin-right:
        4px;

    text-align:
        right;

}


.meal-header-kcal strong {

    color:
        #edf7ef;

    font-size:
        11px;

}


.meal-header-kcal span {

    color:
        rgba(211,229,214,.30);

    font-size:
        7px;

}


.open-food-button {

    height:
        36px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        5px;

    padding:
        0 11px;

    border:
        1px solid rgba(166,207,159,.14);

    border-radius:
        10px;

    background:
        rgba(151,195,145,.065);

    color:
        #b2d2ac;

    font-size:
        8px;

    font-weight:
        600;

    cursor:
        pointer;

}


.open-food-button:hover {

    background:
        rgba(151,195,145,.13);

}


.delete-meal-button {

    width:
        36px;

    height:
        36px;

    border:
        1px solid rgba(223,92,92,.14);

    border-radius:
        10px;

    background:
        rgba(213,73,73,.055);

    color:
        #d88787;

    cursor:
        pointer;

}


.meal-empty {

    min-height:
        185px;

    display:
        flex;

    flex-direction:
        column;

    align-items:
        center;

    justify-content:
        center;

    padding:
        25px;

    text-align:
        center;

}


.meal-empty-icon {

    width:
        46px;

    height:
        46px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    margin-bottom:
        10px;

    border-radius:
        14px;

    background:
        rgba(151,195,145,.07);

    color:
        rgba(166,203,160,.55);

}


.meal-empty strong {

    color:
        rgba(235,244,237,.65);

    font-size:
        10px;

}


.meal-empty span {

    margin-top:
        3px;

    color:
        rgba(211,229,214,.28);

    font-size:
        8px;

}


.meal-empty button {

    margin-top:
        13px;

    border:
        0;

    background:
        transparent;

    color:
        #9fc69a;

    font-size:
        8px;

    cursor:
        pointer;

}


.food-list {

    padding:
        6px 17px;

}


.food-row {

    display:
        grid;

    grid-template-columns:
        minmax(0, 1fr)
        auto
        35px;

    align-items:
        center;

    gap:
        12px;

    padding:
        13px 0;

    border-bottom:
        1px solid rgba(255,255,255,.045);

}


.food-row:last-child {

    border-bottom:
        0;

}


.food-row-main {

    display:
        flex;

    align-items:
        center;

    gap:
        10px;

    min-width:
        0;

}


.food-row-icon {

    width:
        38px;

    height:
        38px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    flex-shrink:
        0;

    border-radius:
        12px;

    background:
        rgba(154,198,147,.09);

    color:
        #a5ca9f;

}


.food-info {

    min-width:
        0;

}


.food-info strong {

    display:
        block;

    overflow:
        hidden;

    color:
        #edf7ef;

    font-size:
        10px;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;

}


.food-info span {

    display:
        block;

    margin-top:
        3px;

    color:
        rgba(213,231,216,.31);

    font-size:
        7px;

}


.food-row-values {

    min-width:
        115px;

    text-align:
        right;

}


.food-row-values strong {

    display:
        block;

    color:
        #dcecdf;

    font-size:
        9px;

}


.food-row-values span {

    display:
        block;

    margin-top:
        3px;

    color:
        rgba(213,231,216,.34);

    font-size:
        7px;

}


.food-row-values b {

    color:
        rgba(213,231,216,.18);

    font-weight:
        400;

}


.delete-food-button {

    width:
        34px;

    height:
        34px;

    border:
        1px solid rgba(220,90,90,.12);

    border-radius:
        9px;

    background:
        rgba(208,67,67,.05);

    color:
        #d98888;

    cursor:
        pointer;

}


.meal-footer {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        12px;

    min-height:
        58px;

    padding:
        13px 17px;

    border-top:
        1px solid rgba(255,255,255,.05);

    background:
        rgba(0,0,0,.07);

}


.meal-footer > div:first-child span {

    display:
        block;

    color:
        rgba(204,224,207,.25);

    font-size:
        6px;

    font-weight:
        700;

    letter-spacing:
        1px;

}


.meal-footer > div:first-child strong {

    display:
        block;

    margin-top:
        2px;

    color:
        rgba(232,242,234,.52);

    font-size:
        8px;

}


.meal-footer-values {

    text-align:
        right;

}


.meal-footer-values strong {

    display:
        block;

    color:
        #e0eee2;

    font-size:
        10px;

}


.meal-footer-values span {

    display:
        block;

    margin-top:
        2px;

    color:
        rgba(212,230,215,.31);

    font-size:
        7px;

}


.no-meals {

    padding:
        75px 25px;

    border:
        1px solid rgba(255,255,255,.075);

    border-radius:
        22px;

    background:
        rgba(255,255,255,.025);

    text-align:
        center;

}


.no-meals-icon {

    width:
        62px;

    height:
        62px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    margin:
        auto auto 14px;

    border-radius:
        19px;

    background:
        rgba(152,195,145,.09);

    color:
        #9fc69a;

    font-size:
        20px;

}


.no-meals > span {

    color:
        #8eae91;

    font-size:
        7px;

    font-weight:
        700;

    letter-spacing:
        1.5px;

}


.no-meals h3 {

    margin:
        7px 0;

    color:
        #edf6ef;

    font-size:
        20px;

}


.no-meals p {

    max-width:
        470px;

    margin:
        0 auto 18px;

    color:
        rgba(214,231,217,.38);

    font-size:
        9px;

}


.food-drawer-overlay {

    position:
        fixed;

    inset:
        0;

    z-index:
        9999;

    display:
        flex;

    justify-content:
        flex-end;

    visibility:
        hidden;

    background:
        rgba(1,8,5,.60);

    opacity:
        0;

    backdrop-filter:
        blur(4px);

    transition:
        .25s ease;

}


.food-drawer-overlay.active {

    visibility:
        visible;

    opacity:
        1;

}


.food-drawer {

    width:
        min(440px, 100%);

    height:
        100%;

    overflow-y:
        auto;

    padding:
        24px;

    border-left:
        1px solid rgba(255,255,255,.08);

    background:

        radial-gradient(
            circle at 20% 5%,
            rgba(77,133,89,.18),
            transparent 28%
        ),

        #0b1d14;

    box-shadow:
        -25px 0 70px rgba(0,0,0,.35);

    transform:
        translateX(100%);

    transition:
        .28s ease;

}


.food-drawer-overlay.active .food-drawer {

    transform:
        translateX(0);

}


.drawer-header {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        15px;

    padding-bottom:
        20px;

    border-bottom:
        1px solid rgba(255,255,255,.06);

}


.drawer-header span {

    color:
        #90b494;

    font-size:
        7px;

    font-weight:
        700;

    letter-spacing:
        1.4px;

}


.drawer-header h3 {

    margin:
        5px 0 0;

    color:
        #eef7f0;

    font-size:
        20px;

}


.drawer-header button {

    width:
        38px;

    height:
        38px;

    border:
        1px solid rgba(255,255,255,.07);

    border-radius:
        11px;

    background:
        rgba(255,255,255,.03);

    color:
        rgba(230,241,232,.55);

    cursor:
        pointer;

}


.drawer-form {

    padding-top:
        22px;

}


.drawer-field {

    margin-bottom:
        20px;

}


.drawer-field label {

    display:
        block;

    margin-bottom:
        8px;

    color:
        rgba(219,234,221,.49);

    font-size:
        9px;

    font-weight:
        600;

}


.drawer-search {

    position:
        relative;

}


.drawer-search > i {

    position:
        absolute;

    top:
        50%;

    left:
        13px;

    color:
        rgba(206,226,210,.30);

    font-size:
        10px;

    transform:
        translateY(-50%);

}


.food-search {

    width:
        100%;

    height:
        44px;

    padding:
        0 13px 0 36px;

    border:
        1px solid rgba(255,255,255,.09);

    border-radius:
        11px;

    outline:
        none;

    background:
        rgba(255,255,255,.04);

    color:
        #edf7ef;

    font-size:
        10px;

}


.food-search::placeholder {

    color:
        rgba(220,235,223,.27);

}


.food-search:focus,
.food-select:focus {

    border-color:
        rgba(169,208,162,.27);

}


.food-select {

    width:
        100%;

    height:
        46px;

    padding:
        0 12px;

    border:
        1px solid rgba(255,255,255,.09);

    border-radius:
        11px;

    outline:
        none;

    background:
        #153025;

    color:
        #edf7ef;

    font-size:
        10px;

}


.food-select option {

    background:
        #153025;

    color:
        white;

}


.food-preview {

    min-height:
        140px;

    margin:
        5px 0 20px;

    padding:
        18px;

    border:
        1px solid rgba(255,255,255,.07);

    border-radius:
        16px;

    background:
        rgba(255,255,255,.025);

}


.preview-placeholder {

    min-height:
        102px;

    display:
        flex;

    flex-direction:
        column;

    align-items:
        center;

    justify-content:
        center;

    gap:
        8px;

    color:
        rgba(213,231,216,.28);

    font-size:
        8px;

    text-align:
        center;

}


.preview-placeholder i {

    color:
        rgba(160,199,154,.45);

    font-size:
        21px;

}


.food-preview-name {

    color:
        #eff7f0;

    font-size:
        13px;

    font-weight:
        600;

}


.food-preview-portion {

    margin-top:
        3px;

    color:
        rgba(213,231,216,.32);

    font-size:
        8px;

}


.food-preview-kcal {

    margin-top:
        15px;

    color:
        #e6f1e7;

    font-size:
        22px;

    font-weight:
        700;

}


.food-preview-kcal small {

    color:
        rgba(213,231,216,.34);

    font-size:
        8px;

    font-weight:
        400;

}


.food-preview-macros {

    display:
        grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:
        7px;

    margin-top:
        13px;

}


.food-preview-macros > div {

    padding:
        10px;

    border-radius:
        11px;

    background:
        rgba(255,255,255,.03);

}


.food-preview-macros span {

    display:
        block;

    color:
        rgba(207,226,210,.28);

    font-size:
        6px;

}


.food-preview-macros strong {

    display:
        block;

    margin-top:
        3px;

    color:
        #e3efe5;

    font-size:
        10px;

}


.drawer-submit {

    width:
        100%;

    height:
        46px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        8px;

    border:
        0;

    border-radius:
        12px;

    background:

        linear-gradient(
            135deg,
            #afd1a4,
            #dce873
        );

    color:
        #102417;

    font-size:
        10px;

    font-weight:
        700;

    cursor:
        pointer;

}


.custom-food-divider {

    margin-top: 22px;
    padding-top: 20px;

    border-top:
        1px solid rgba(255,255,255,.06);

    text-align: center;
}


.custom-food-divider span {

    display: block;

    margin-bottom: 9px;

    color:
        rgba(214,231,217,.32);

    font-size: 8px;
}


.custom-food-divider button {

    border: 0;

    background: transparent;

    color: #a9cca2;

    font-size: 9px;

    font-weight: 600;

    cursor: pointer;
}


.custom-food-divider button i {

    margin-right: 5px;
}


.custom-food-form {

    display: none;

    padding-top: 22px;
}


.custom-food-form.active {

    display: block;
}


.custom-form-header {

    display: flex;

    align-items: center;

    gap: 12px;

    margin-bottom: 17px;
}


.custom-form-header > button {

    width: 37px;
    height: 37px;

    border:
        1px solid rgba(255,255,255,.07);

    border-radius: 10px;

    background:
        rgba(255,255,255,.035);

    color: #accaaa;

    cursor: pointer;
}


.custom-form-header span {

    display: block;

    color: #8faf91;

    font-size: 6px;

    font-weight: 700;

    letter-spacing: 1.2px;
}


.custom-form-header h4 {

    margin: 3px 0 0;

    color: #eef7ef;

    font-size: 15px;
}


.custom-warning {

    display: flex;

    align-items: flex-start;

    gap: 9px;

    margin-bottom: 20px;

    padding: 11px;

    border:
        1px solid rgba(178,204,112,.10);

    border-radius: 11px;

    background:
        rgba(180,202,103,.045);

    color:
        rgba(218,232,190,.55);

    font-size: 8px;

    line-height: 1.5;
}


.custom-warning i {

    margin-top: 2px;

    color: #cbd878;
}


.custom-input {

    width: 100%;
    height: 44px;

    padding: 0 12px;

    border:
        1px solid rgba(255,255,255,.09);

    border-radius: 11px;

    outline: none;

    background:
        rgba(255,255,255,.04);

    color: white;

    font-size: 10px;
}


.custom-input::placeholder {

    color:
        rgba(214,231,217,.25);
}


.custom-input:focus {

    border-color:
        rgba(169,208,162,.27);
}


.custom-unit-row {

    display: flex;

    align-items: center;

    border:
        1px solid rgba(255,255,255,.09);

    border-radius: 11px;

    background:
        rgba(255,255,255,.04);
}


.custom-unit-row .custom-input {

    border: 0;

    background: transparent;
}


.custom-unit-row span {

    padding-right: 12px;

    color:
        rgba(214,231,217,.34);

    font-size: 8px;
}


.custom-macro-grid {

    display: grid;

    grid-template-columns:
        repeat(3,1fr);

    gap: 8px;

    margin-bottom: 20px;
}


.custom-macro-grid label {

    display: block;

    margin-bottom: 7px;

    color:
        rgba(218,234,221,.48);

    font-size: 8px;
}

</style>

@stop



{{-- =========================================================
     JS
========================================================= --}}

@section('js')

<script>

/* =============================================================
   DRAWER DE PLATILLOS (NUEVO)
============================================================= */
function openDishDrawer(mealId) {
    const overlay = document.getElementById('dishDrawerOverlay' + mealId);
    if (overlay) {
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function forceCloseDishDrawer(mealId) {
    const overlay = document.getElementById('dishDrawerOverlay' + mealId);
    if (overlay) {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function closeDishDrawer(mealId, event) {
    const overlay = document.getElementById('dishDrawerOverlay' + mealId);
    if (overlay && event.target === overlay) {
        forceCloseDishDrawer(mealId);
    }
}


document.addEventListener('DOMContentLoaded', function () {

    console.log('NutriLife Diet Builder JS cargado correctamente');

    /* BUSCADOR DE ALIMENTOS */
    document.querySelectorAll('.food-search').forEach(function (search) {

        search.addEventListener('input', function () {

            const select = document.getElementById(this.dataset.target);
            if (!select) return;

            const text = this.value.toLowerCase().trim();

            Array.from(select.options).forEach(function (option) {
                if (!option.value) {
                    option.hidden = false;
                    return;
                }
                const name = (option.dataset.name || '').toLowerCase();
                option.hidden = !name.includes(text);
            });

        });

    });


    /* CAMBIO DE ALIMENTO */
    document.querySelectorAll('.food-select').forEach(function (select) {

        select.addEventListener('change', function () {

            const mealId = this.id.replace('foodSelect', '');
            const modeInput = document.getElementById('measureMode' + mealId);
            const quantityInput = document.getElementById('quantity' + mealId);
            const portionButton = document.getElementById('measurePortion' + mealId);
            const gramsButton = document.getElementById('measureGrams' + mealId);

            if (modeInput) modeInput.value = 'portion';

            if (quantityInput) {
                quantityInput.value = 1;
                quantityInput.min = 0.5;
                quantityInput.step = 0.5;
            }

            if (portionButton) portionButton.classList.add('active');
            if (gramsButton) gramsButton.classList.remove('active');

            updateFoodPreview(mealId);

        });

    });


    /* ESC CIERRA DRAWERS */
    document.addEventListener('keydown', function (event) {

        if (event.key !== 'Escape') return;

        document.querySelectorAll('.food-drawer-overlay.active').forEach(function (overlay) {
            overlay.classList.remove('active');
        });

        document.body.style.overflow = '';

    });

});


function toggleMealForm() {
    const form = document.getElementById('mealForm');
    if (form) form.classList.toggle('active');
}


function openFoodDrawer(mealId) {
    const overlay = document.getElementById('drawerOverlay' + mealId);
    if (overlay) {
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        updateFoodPreview(mealId);
    }
}


function forceCloseFoodDrawer(mealId) {
    const overlay = document.getElementById('drawerOverlay' + mealId);
    if (overlay) {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
}


function closeFoodDrawer(mealId, event) {
    const overlay = document.getElementById('drawerOverlay' + mealId);
    if (overlay && event.target === overlay) {
        forceCloseFoodDrawer(mealId);
    }
}


function setMeasureMode(mealId, mode) {

    const modeInput = document.getElementById('measureMode' + mealId);
    const quantityInput = document.getElementById('quantity' + mealId);
    const portionButton = document.getElementById('measurePortion' + mealId);
    const gramsButton = document.getElementById('measureGrams' + mealId);
    const select = document.getElementById('foodSelect' + mealId);

    if (!modeInput || !quantityInput || !portionButton || !gramsButton) return;

    modeInput.value = mode;

    portionButton.classList.toggle('active', mode === 'portion');
    gramsButton.classList.toggle('active', mode === 'grams');

    if (mode === 'portion') {
        quantityInput.value = 1;
        quantityInput.min = 0.5;
        quantityInput.step = 0.5;
    } else {
        let grams = 100;
        if (select && select.selectedIndex >= 0) {
            const option = select.options[select.selectedIndex];
            if (option && option.value && option.dataset.portion) {
                grams = parseFloat(option.dataset.portion) || 100;
            }
        }
        quantityInput.value = grams;
        quantityInput.min = 1;
        quantityInput.step = 1;
    }

    updateFoodPreview(mealId);
}


function changeQuantity(mealId, direction) {

    const quantityInput = document.getElementById('quantity' + mealId);
    const modeInput = document.getElementById('measureMode' + mealId);

    if (!quantityInput || !modeInput) return;

    let value = parseFloat(quantityInput.value) || 0;
    const step = modeInput.value === 'portion' ? 0.5 : 10;

    value += direction * step;

    if (modeInput.value === 'portion') {
        value = Math.max(0.5, value);
    } else {
        value = Math.max(1, value);
    }

    quantityInput.value = value;
    updateFoodPreview(mealId);
}


function updateFoodPreview(mealId) {

    const select = document.getElementById('foodSelect' + mealId);
    const quantityInput = document.getElementById('quantity' + mealId);
    const gramsInput = document.getElementById('grams' + mealId);
    const modeInput = document.getElementById('measureMode' + mealId);
    const quantityUnit = document.getElementById('quantityUnit' + mealId);
    const equivalence = document.getElementById('portionEquivalence' + mealId);
    const preview = document.getElementById('foodPreview' + mealId);

    if (!select || !quantityInput || !gramsInput || !modeInput || !preview) return;

    const option = select.options[select.selectedIndex];

    if (!option || !option.value) {
        preview.innerHTML =
            '<div class="preview-placeholder">' +
                '<i class="fas fa-apple-whole"></i>' +
                '<span>Selecciona un alimento para ver sus valores nutricionales.</span>' +
            '</div>';
        if (equivalence) equivalence.textContent = 'Selecciona un alimento.';
        return;
    }

    const portionGrams = parseFloat(option.dataset.portion) || 100;
    const portionDescription = option.dataset.portionDescription || '1 porción';
    const quantity = parseFloat(quantityInput.value) || 0;
    const mode = modeInput.value;

    let grams = 0;
    let visibleDescription = '';

    if (mode === 'portion') {
        grams = quantity * portionGrams;
        if (quantityUnit) {
            const description = portionDescription.toLowerCase();
            quantityUnit.textContent = description.includes('pieza') ? (quantity === 1 ? 'pieza' : 'piezas') : (quantity === 1 ? 'porción' : 'porciones');
        }
        visibleDescription = quantity + ' × ' + portionDescription;
        if (equivalence) {
            equivalence.innerHTML = '<span>' + visibleDescription + '</span><strong>≈ ' + grams.toFixed(0) + ' g</strong>';
        }
    } else {
        grams = quantity;
        if (quantityUnit) quantityUnit.textContent = 'g';
        const equivalentPortions = portionGrams > 0 ? grams / portionGrams : 0;
        visibleDescription = grams.toFixed(0) + ' g';
        if (equivalence) {
            equivalence.innerHTML = '<span>' + grams.toFixed(0) + ' g</span><strong>≈ ' + equivalentPortions.toFixed(2) + ' porciones</strong>';
        }
    }

    gramsInput.value = grams.toFixed(2);

    const factor = portionGrams > 0 ? grams / portionGrams : 0;
    const kcal = (parseFloat(option.dataset.kcal) || 0) * factor;
    const protein = (parseFloat(option.dataset.protein) || 0) * factor;
    const carbs = (parseFloat(option.dataset.carbs) || 0) * factor;
    const fat = (parseFloat(option.dataset.fat) || 0) * factor;

    preview.innerHTML =
        '<div class="food-preview-name">' + option.text.trim() + '</div>' +
        '<div class="food-preview-portion">' + visibleDescription + ' · ' + grams.toFixed(0) + ' g</div>' +
        '<div class="food-preview-kcal">' + kcal.toFixed(0) + ' <small>kcal</small></div>' +
        '<div class="food-preview-macros">' +
            '<div><span>PROTEÍNA</span><strong>' + protein.toFixed(1) + 'g</strong></div>' +
            '<div><span>CARBOHIDRATOS</span><strong>' + carbs.toFixed(1) + 'g</strong></div>' +
            '<div><span>GRASAS</span><strong>' + fat.toFixed(1) + 'g</strong></div>' +
        '</div>';
}


function showCustomFoodForm(mealId) {
    const customForm = document.getElementById('customFoodForm' + mealId);
    if (!customForm) return;

    const drawer = customForm.closest('.food-drawer');
    if (drawer) {
        const catalogForm = drawer.querySelector('.drawer-form');
        const divider = drawer.querySelector('.custom-food-divider');
        if (catalogForm) catalogForm.style.display = 'none';
        if (divider) divider.style.display = 'none';
    }
    customForm.classList.add('active');
}


function hideCustomFoodForm(mealId) {
    const customForm = document.getElementById('customFoodForm' + mealId);
    if (!customForm) return;

    const drawer = customForm.closest('.food-drawer');
    customForm.classList.remove('active');

    if (drawer) {
        const catalogForm = drawer.querySelector('.drawer-form');
        const divider = drawer.querySelector('.custom-food-divider');
        if (catalogForm) catalogForm.style.display = '';
        if (divider) divider.style.display = '';
    }
}
</script>

@stop
>>>>>>> 56f1ba86bc25aa2407be1779b09730fe5ce1b82e
