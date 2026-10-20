@extends('adminlte::page')

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
