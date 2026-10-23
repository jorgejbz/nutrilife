@extends('adminlte::page')

@section('title', $plan->name.' | NutriLife')

{{-- =========================================================
     CONSTRUCTOR NUTRICIONAL
     Diseño de la versión nutrilife_planesAlim (resumen del día,
     tarjetas de comida y drawers para agregar alimento, platillo
     o alimento manual) con lo que ya tenía la versión final:
     paciente y alergias, indicaciones, horario de cada comida,
     editar comida y gramos, avisos clínicos y duplicar el plan.

     Los totales salen de los modelos (DietPlan::totals(),
     DietPlanMeal::totals(), DietPlanItem::nutrients()): catálogo =
     valores de foods por porción × gramos / porción; personalizado
     = custom_* (aporte total del renglón).
     ========================================================= --}}
@php
    $patient = $plan->patient;
    $patientName = $patient ? $patient->first_name.' '.$patient->last_name : 'Paciente eliminado';

    $totals = $plan->totals();
    $targets = $plan->targets();
    $progress = $plan->progress();
    $remaining = $targets['kcal'] - $totals['kcal'];

    $macroRows = [
        'protein_g' => ['label' => 'PROTEÍNA', 'short' => 'P', 'class' => 'protein'],
        'carbs_g' => ['label' => 'CARBOHIDRATOS', 'short' => 'C', 'class' => 'carbs'],
        'lipids_g' => ['label' => 'GRASAS', 'short' => 'G', 'class' => 'fats'],
    ];

    $severityLabels = ['leve' => 'Leve', 'moderada' => 'Moderada', 'severa' => 'Severa'];

    // Los alimentos personalizados no tienen alérgenos ni restricciones en la BD
    $hasClinicalFlags = (bool) ($patient?->allergens->isNotEmpty() || $patient?->pathologies->isNotEmpty());

    $fmt = fn (float $value, int $decimals = 0) => number_format($value, $decimals);
    $trimNumber = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');

    // Drawer que se reabre si la validación del servidor falla
    $reopenDrawer = old('_drawer');
    $reopenMeal = old('_drawer_meal');
    $mealFormHasError = $errors->has('meal_name') && ! $reopenDrawer && ! old('_meal_edit');
@endphp

{{-- =========================================================
     HEADER
     ========================================================= --}}
@section('content_header')
<div class="dp dp-builder-header">
    <div>
        <a href="{{ route('diet-plans.index') }}" class="dp-breadcrumb">
            <i class="fas fa-arrow-left"></i>
            Planes alimenticios
        </a>

        <span class="dp-builder-eyebrow">
            <i class="fas fa-leaf"></i>
            CONSTRUCTOR NUTRICIONAL
        </span>

        <h1>{{ $plan->name }}</h1>

        <p>
            <span><i class="far fa-user" aria-hidden="true"></i> {{ $patientName }}</span>
            <span>
                <i class="far fa-calendar" aria-hidden="true"></i>
                {{ $plan->starts_at->format('d/m/Y') }}
                — {{ $plan->ends_at ? $plan->ends_at->format('d/m/Y') : 'actualidad' }}
            </span>
        </p>
    </div>

    <div class="dp-header-actions">
        <span class="dp-plan-badge {{ $plan->active ? 'is-active' : '' }}">
            <span class="dp-status-dot"></span>
            {{ $plan->active ? 'Plan activo' : 'Plan finalizado' }}
        </span>

        <button type="button" class="dp-btn-secondary" data-dp-open="duplicate">
            <i class="far fa-copy" aria-hidden="true"></i>
            Duplicar / asignar
        </button>

        <a href="{{ route('diet-plans.edit', $plan) }}" class="dp-btn">
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
<div class="dp dp-builder" id="dietBuilder">

    {{-- =====================================================
         ALERTAS
         ===================================================== --}}
    @if(session('success'))
        <div class="dp-alert is-success" role="status">
            <div class="dp-alert-icon"><i class="fas fa-check"></i></div>
            <div>
                <strong>¡Listo!</strong>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="dp-alert is-error" role="alert">
            <div class="dp-alert-icon"><i class="fas fa-exclamation"></i></div>
            <div>
                <strong>Revisa la información</strong>
                <span>{{ $errors->first() }}</span>
            </div>
        </div>
    @endif

    @unless($plan->active)
        <div class="dp-alert is-success" role="note">
            <div class="dp-alert-icon"><i class="fas fa-flag-checkered"></i></div>
            <div>
                <strong>Plan finalizado</strong>
                <span>Puedes consultarlo y ajustarlo; para reactivarlo usa «Editar datos».</span>
            </div>
        </div>
    @endunless

    {{-- =====================================================
         RESUMEN DEL DÍA
         ===================================================== --}}
    <section class="dp-daily-summary" aria-label="Resumen del día">

        <div class="dp-daily-main">
            <div class="dp-daily-main-top">
                <div>
                    <span class="dp-summary-label">CALORÍAS DEL DÍA</span>
                    <div class="dp-daily-kcal">
                        <strong>{{ $fmt($totals['kcal']) }}</strong>
                        <span>/ {{ $fmt($targets['kcal']) }} kcal</span>
                    </div>
                </div>

                <div class="dp-daily-percentage {{ $progress['kcal'] > 105 ? 'is-over' : '' }}">
                    {{ $fmt($progress['kcal']) }}%
                </div>
            </div>

            <div class="dp-progress-track"
                 role="progressbar"
                 aria-label="Calorías del día"
                 aria-valuemin="0"
                 aria-valuemax="100"
                 aria-valuenow="{{ (int) min($progress['kcal'], 100) }}">
                <div class="dp-progress-value {{ $progress['kcal'] > 105 ? 'is-over' : '' }}"
                     style="width: {{ min($progress['kcal'], 100) }}%"></div>
            </div>

            <div class="dp-daily-remaining">
                @if($targets['kcal'] <= 0)
                    Define las calorías objetivo en «Editar datos».
                @elseif($remaining >= 0)
                    Faltan <strong>{{ $fmt($remaining) }} kcal</strong> para alcanzar el objetivo.
                @else
                    El plan supera el objetivo por <strong>{{ $fmt(abs($remaining)) }} kcal</strong>.
                @endif
            </div>
        </div>

        <div class="dp-macro-progress-grid">
            @foreach($macroRows as $key => $meta)
                <div class="dp-macro-progress-card">
                    <div class="dp-macro-card-top">
                        <div class="dp-macro-symbol {{ $meta['class'] }}" aria-hidden="true">{{ $meta['short'] }}</div>
                        <span>{{ $meta['label'] }}</span>
                    </div>

                    <div class="dp-macro-current">
                        <strong>{{ $fmt($totals[$key], 1) }}g</strong>
                        <small>de {{ $fmt($targets[$key]) }}g</small>
                    </div>

                    <div class="dp-progress-track is-small" aria-hidden="true">
                        <div class="dp-progress-value {{ $progress[$key] > 110 ? 'is-over' : '' }}"
                             style="width: {{ min($progress[$key], 100) }}%"></div>
                    </div>

                    <div class="dp-macro-percent">{{ $fmt($progress[$key]) }}%</div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- =====================================================
         PACIENTE E INDICACIONES
         ===================================================== --}}
    <div class="dp-context-grid">

        <aside class="dp-context-card" aria-labelledby="patientCardTitle">
            <div class="dp-context-head">
                <span class="dp-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($patient?->first_name ?? 'P', 0, 1)) }}</span>
                <div>
                    <span>PACIENTE</span>
                    <h2 id="patientCardTitle">{{ $patientName }}</h2>
                </div>
            </div>

            @if($patient?->goal)
                <p class="dp-context-goal"><i class="fas fa-bullseye" aria-hidden="true"></i> {{ $patient->goal }}</p>
            @endif

            <div class="dp-context-block">
                <span>ALERGIAS E INTOLERANCIAS</span>
                <div class="dp-tags">
                    @forelse($patient?->allergens ?? [] as $allergen)
                        <span class="dp-tag is-allergen">
                            {{ $allergen->name }}
                            @if($allergen->pivot->severity)
                                · {{ $severityLabels[$allergen->pivot->severity] ?? $allergen->pivot->severity }}
                            @endif
                        </span>
                    @empty
                        <small class="dp-muted">Sin alergias registradas.</small>
                    @endforelse
                </div>
            </div>

            <div class="dp-context-block">
                <span>PATOLOGÍAS</span>
                <div class="dp-tags">
                    @forelse($patient?->pathologies ?? [] as $pathology)
                        <span class="dp-tag">{{ $pathology->name }}</span>
                    @empty
                        <small class="dp-muted">Sin patologías registradas.</small>
                    @endforelse
                </div>
            </div>

            <div class="dp-context-block">
                <span>ÚLTIMA EVALUACIÓN</span>
                @if($latestEvaluation)
                    <dl class="dp-clinical-grid">
                        <div><dt>Peso</dt><dd>{{ $latestEvaluation->weight !== null ? number_format((float) $latestEvaluation->weight, 1).' kg' : '—' }}</dd></div>
                        <div><dt>Estatura</dt><dd>{{ $latestEvaluation->height !== null ? number_format((float) $latestEvaluation->height, 0).' cm' : '—' }}</dd></div>
                        <div><dt>IMC</dt><dd>{{ $latestEvaluation->bmi !== null ? number_format((float) $latestEvaluation->bmi, 1) : '—' }}</dd></div>
                        <div><dt>Grasa</dt><dd>{{ $latestEvaluation->body_fat !== null ? number_format((float) $latestEvaluation->body_fat, 1).' %' : '—' }}</dd></div>
                    </dl>
                    <small class="dp-muted">Del {{ $latestEvaluation->evaluation_date->format('d/m/Y') }}</small>
                @else
                    <small class="dp-muted">Sin evaluaciones registradas.</small>
                @endif
            </div>

            @if($lastConsultation?->recommendations || $lastConsultation?->observations)
                <div class="dp-context-block">
                    <span>ÚLTIMA CONSULTA · {{ $lastConsultation->consultation_at->format('d/m/Y') }}</span>
                    <p class="dp-muted">{{ \Illuminate\Support\Str::limit($lastConsultation->recommendations ?: $lastConsultation->observations, 180) }}</p>
                </div>
            @endif

            @if($patient && ! $patient->trashed())
                <a href="{{ route('pacientes.show', $patient) }}" class="dp-link">
                    Ver expediente <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            @endif
        </aside>

        <section class="dp-context-card" aria-labelledby="instructionsTitle">
            <div class="dp-context-head">
                <span class="dp-meal-icon" aria-hidden="true"><i class="far fa-clipboard"></i></span>
                <div>
                    <span>INDICACIONES DEL NUTRIÓLOGO</span>
                    <h2 id="instructionsTitle">{{ $plan->goal ?: 'Indicaciones del plan' }}</h2>
                </div>
                <a href="{{ route('diet-plans.edit', $plan) }}#instructions"
                   class="dp-icon-btn"
                   title="Editar objetivo e indicaciones"
                   aria-label="Editar objetivo e indicaciones">
                    <i class="far fa-edit" aria-hidden="true"></i>
                </a>
            </div>

            @if($plan->instructions)
                <ul class="dp-instructions-list">
                    @foreach(preg_split('/\R+/', trim($plan->instructions)) as $line)
                        @if(trim($line) !== '')
                            <li>{{ trim($line) }}</li>
                        @endif
                    @endforeach
                </ul>
            @else
                <p class="dp-muted">
                    Sin indicaciones.
                    <a href="{{ route('diet-plans.edit', $plan) }}#instructions" class="dp-link nl-m-0">Agregarlas</a>
                </p>
            @endif
        </section>
    </div>

    {{-- =====================================================
         COMIDAS
         ===================================================== --}}
    <section class="dp-meals-section" aria-labelledby="mealsTitle">

        <div class="dp-meals-heading">
            <div>
                <span class="dp-section-eyebrow">
                    <i class="fas fa-utensils"></i>
                    DISTRIBUCIÓN DEL DÍA
                </span>
                <h2 id="mealsTitle">Comidas</h2>
                <p>Construye el plan agregando alimentos a cada tiempo de comida.</p>
            </div>

            <button type="button"
                    class="dp-btn-primary"
                    data-dp-toggle-meal-form
                    aria-controls="mealForm"
                    aria-expanded="{{ $mealFormHasError ? 'true' : 'false' }}">
                <i class="fas fa-plus"></i>
                Agregar comida
            </button>
        </div>

        {{-- FORMULARIO NUEVA COMIDA --}}
        <div class="dp-new-meal-form" id="mealForm" @unless($mealFormHasError) hidden @endunless>
            <form action="{{ route('diet-plans.meals.store', $plan) }}" method="POST">
                @csrf

                <div class="dp-meal-form-field">
                    <label for="meal_name">Nombre de la comida</label>
                    <input type="text"
                           name="meal_name"
                           id="meal_name"
                           list="mealSuggestions"
                           maxlength="100"
                           value="{{ $mealFormHasError ? old('meal_name') : '' }}"
                           placeholder="Ej. Desayuno, Colación, Cena..."
                           required>
                    <datalist id="mealSuggestions">
                        @foreach($mealSuggestions as $suggestion)
                            <option value="{{ $suggestion }}">
                        @endforeach
                    </datalist>
                </div>

                <div class="dp-meal-form-field is-time">
                    <label for="meal_time">Horario <small>(opcional)</small></label>
                    <input type="time" name="meal_time" id="meal_time" value="{{ $mealFormHasError ? old('meal_time') : '' }}">
                </div>

                <div class="dp-meal-form-actions">
                    <button type="button" class="dp-btn-secondary" data-dp-toggle-meal-form>Cancelar</button>
                    <button type="submit" class="dp-btn-primary" data-loading-text="Creando…">
                        <i class="fas fa-plus"></i>
                        Crear comida
                    </button>
                </div>
            </form>
        </div>

        @if($foods->isEmpty())
            <div class="dp-alert is-error dp-food-catalog-empty" role="note">
                <div class="dp-alert-icon"><i class="fas fa-database"></i></div>
                <div>
                    <strong>Catálogo vacío</strong>
                    <span>El catálogo de alimentos (tabla foods) está vacío. Cárgalo para poder agregar alimentos del catálogo.</span>
                </div>
            </div>
        @endif

        @if($plan->meals->isEmpty())

            {{-- SIN COMIDAS --}}
            <div class="dp-no-meals">
                <div class="dp-no-meals-icon"><i class="fas fa-bowl-food"></i></div>
                <span>COMIENZA EL PLAN</span>
                <h3>Aún no has creado comidas.</h3>
                <p>
                    Agrega desayuno, colaciones, comida, cena
                    o cualquier otro tiempo que necesite el paciente.
                </p>
                <button type="button" class="dp-btn-primary" data-dp-toggle-meal-form>
                    <i class="fas fa-plus"></i>
                    Agregar primera comida
                </button>
            </div>

        @else

            {{-- GRID DE COMIDAS --}}
            <div class="dp-meals-list">
                @foreach($plan->meals as $meal)
                    @php
                        $mealTotals = $meal->totals();
                        $mealEditOpen = old('_meal_edit') == $meal->id && $errors->any();
                    @endphp

                    <article class="dp-meal-card" id="comida-{{ $meal->id }}" aria-labelledby="meal-title-{{ $meal->id }}">

                        {{-- HEADER COMIDA --}}
                        <div class="dp-meal-header">
                            <div class="dp-meal-title">
                                <div class="dp-meal-icon" aria-hidden="true"><i class="{{ $meal->icon() }}"></i></div>
                                <div>
                                    <span>
                                        COMIDA {{ $loop->iteration }}
                                        @if($meal->timeLabel())
                                            · <i class="far fa-clock" aria-hidden="true"></i> {{ $meal->timeLabel() }}
                                        @endif
                                    </span>
                                    <h3 id="meal-title-{{ $meal->id }}">{{ $meal->meal_name }}</h3>
                                    @if($meal->notes)
                                        <p class="dp-meal-notes"><i class="fas fa-info-circle" aria-hidden="true"></i> {{ $meal->notes }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="dp-meal-header-right">
                                <div class="dp-meal-header-kcal">
                                    <strong>{{ $fmt($mealTotals['kcal']) }}</strong>
                                    <span>kcal</span>
                                </div>

                                {{-- PLATILLO COMPLETO --}}
                                <button type="button"
                                        class="dp-open-dish-button"
                                        data-dp-open="dish"
                                        data-meal-id="{{ $meal->id }}"
                                        data-meal-name="{{ $meal->meal_name }}"
                                        data-action="{{ route('diet-plans.meals.dishes.store', [$plan, $meal]) }}"
                                        title="Insertar una receta completa"
                                        @disabled($dishes->isEmpty())>
                                    <i class="fas fa-utensils"></i>
                                    Platillo
                                </button>

                                {{-- ALIMENTO INDIVIDUAL (catálogo o manual) --}}
                                <button type="button"
                                        class="dp-open-food-button"
                                        data-dp-open="food"
                                        data-meal-id="{{ $meal->id }}"
                                        data-meal-name="{{ $meal->meal_name }}"
                                        data-action="{{ route('diet-plans.items.store', [$plan, $meal]) }}"
                                        data-custom-action="{{ route('diet-plans.items.custom.store', [$plan, $meal]) }}">
                                    <i class="fas fa-plus"></i>
                                    Alimento
                                </button>

                                <button type="button"
                                        class="dp-icon-btn"
                                        title="Editar nombre, horario e indicación"
                                        aria-label="Editar {{ $meal->meal_name }}"
                                        aria-controls="mealEdit{{ $meal->id }}"
                                        aria-expanded="{{ $mealEditOpen ? 'true' : 'false' }}"
                                        data-dp-toggle="mealEdit{{ $meal->id }}">
                                    <i class="far fa-edit" aria-hidden="true"></i>
                                </button>

                                <form action="{{ route('diet-plans.meals.destroy', [$plan, $meal]) }}"
                                      method="POST"
                                      data-confirm="¿Eliminar {{ $meal->meal_name }}? También se eliminarán sus {{ $meal->items->count() }} alimentos."
                                      data-confirm-title="Eliminar comida"
                                      data-confirm-accept="Eliminar"
                                      data-confirm-variant="danger"
                                      data-confirm-icon="fa-trash-alt">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="dp-icon-btn is-danger"
                                            title="Eliminar comida"
                                            aria-label="Eliminar {{ $meal->meal_name }}"
                                            data-loading-text="">
                                        <i class="far fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- EDITAR COMIDA (PATCH diet-plans.meals.update) --}}
                        <form action="{{ route('diet-plans.meals.update', [$plan, $meal]) }}"
                              method="POST"
                              class="dp-meal-edit"
                              id="mealEdit{{ $meal->id }}"
                              @unless($mealEditOpen) hidden @endunless>
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="_meal_edit" value="{{ $meal->id }}">

                            <div class="dp-meal-form-field">
                                <label for="meal-name-{{ $meal->id }}">Nombre</label>
                                <input type="text" id="meal-name-{{ $meal->id }}" name="meal_name" value="{{ $mealEditOpen ? old('meal_name') : $meal->meal_name }}" maxlength="100" list="mealSuggestions" required>
                            </div>
                            <div class="dp-meal-form-field is-time">
                                <label for="meal-time-{{ $meal->id }}">Horario</label>
                                <input type="time" id="meal-time-{{ $meal->id }}" name="meal_time" value="{{ $mealEditOpen ? old('meal_time') : $meal->timeLabel() }}">
                            </div>
                            <div class="dp-meal-form-field nl-basis-full">
                                <label for="meal-notes-{{ $meal->id }}">Indicación de esta comida</label>
                                <input type="text" id="meal-notes-{{ $meal->id }}" name="notes" value="{{ $mealEditOpen ? old('notes') : $meal->notes }}" maxlength="255" placeholder="Ej. Sin sal añadida; preparar al vapor">
                            </div>
                            <div class="dp-meal-form-actions">
                                <button type="button" class="dp-btn-secondary" data-dp-toggle="mealEdit{{ $meal->id }}">Cancelar</button>
                                <button type="submit" class="dp-btn-primary" data-loading-text="Guardando…">Guardar</button>
                            </div>
                        </form>

                        {{-- ALIMENTOS --}}
                        @if($meal->items->isEmpty())

                            <div class="dp-meal-empty">
                                <div class="dp-meal-empty-icon"><i class="fas fa-carrot"></i></div>
                                <strong>Sin alimentos</strong>
                                <span>Agrega el primer alimento de esta comida.</span>
                                <button type="button"
                                        data-dp-open="food"
                                        data-meal-id="{{ $meal->id }}"
                                        data-meal-name="{{ $meal->meal_name }}"
                                        data-action="{{ route('diet-plans.items.store', [$plan, $meal]) }}"
                                        data-custom-action="{{ route('diet-plans.items.custom.store', [$plan, $meal]) }}">
                                    <i class="fas fa-plus"></i>
                                    Agregar alimento
                                </button>
                            </div>

                        @else

                            <ul class="dp-food-list">
                                @foreach($meal->entries() as $entry)
                                    @if($entry['dish_id'] === null)
                                        @include('diet-plans.partials.item-row', ['item' => $entry['items']->first(), 'inDish' => false])
                                    @else
                                        @include('diet-plans.partials.dish-group', ['dishId' => $entry['dish_id'], 'dishItems' => $entry['items']])
                                    @endif
                                @endforeach
                            </ul>

                        @endif

                        {{-- FOOTER / TOTAL --}}
                        <div class="dp-meal-footer">
                            <div>
                                <span>TOTAL</span>
                                <strong>{{ mb_strtoupper($meal->meal_name) }}</strong>
                            </div>

                            <div class="dp-meal-footer-values">
                                <strong>{{ $fmt($mealTotals['kcal']) }} kcal</strong>
                                <span>
                                    {{ $fmt($mealTotals['protein_g'], 1) }}P
                                    ·
                                    {{ $fmt($mealTotals['carbs_g'], 1) }}C
                                    ·
                                    {{ $fmt($mealTotals['lipids_g'], 1) }}G
                                </span>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

        @endif
    </section>


    {{-- =====================================================
         DRAWER: AGREGAR PLATILLO (búsqueda e imágenes)
         Copia los ingredientes del platillo a la comida.
         ===================================================== --}}
    <div class="dp-drawer-overlay"
         id="dishDrawer"
         data-dp-drawer="dish"
         @if($reopenDrawer === 'dish') data-reopen-meal="{{ $reopenMeal }}" @endif
         aria-hidden="true">
        <aside class="dp-drawer" role="dialog" aria-modal="true" aria-labelledby="dishDrawerTitle">

            <div class="dp-drawer-header">
                <div>
                    <span>INSERTAR RECETA COMPLETA</span>
                    <h3 id="dishDrawerTitle">Platillos para <span data-meal-label>la comida</span></h3>
                </div>
                <button type="button" data-dp-close aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form action="" method="POST" class="dp-drawer-form" data-dp-form="dish">
                @csrf
                <input type="hidden" name="_drawer" value="dish">
                <input type="hidden" name="_drawer_meal" value="" data-meal-input>

                {{-- ID del platillo seleccionado --}}
                <input type="hidden" name="diet_plan_dish_id" id="selectedDishId" value="{{ $reopenDrawer === 'dish' ? old('diet_plan_dish_id') : '' }}">

                <div class="dp-drawer-field">
                    <label for="dishSearch">
                        Buscar receta o platillo
                        <a href="{{ route('dishes.index') }}" target="_blank" rel="noopener">
                            Ver catálogo completo <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                            <span class="visually-hidden">(se abre en otra pestaña)</span>
                        </a>
                    </label>
                    <div class="dp-drawer-search">
                        <i class="fas fa-search"></i>
                        <input type="search"
                               id="dishSearch"
                               class="dp-search-input"
                               placeholder="Ej. Omelette, Salmón, Tacos..."
                               autocomplete="off">
                    </div>
                </div>

                <div class="dp-drawer-field">
                    <span class="dp-drawer-label">Selecciona un platillo</span>

                    <div class="dp-dish-cards-list" id="dishList" role="listbox" aria-label="Platillos">
                        @foreach($dishes as $dish)
                            @php
                                $dishTotals = $dish->totals();
                                $dishMissing = $dish->missingNutrientsCount();
                                $dishEmpty = $dish->items->isEmpty();
                                $dishWarnings = $dish->items->whereNotNull('food_id')
                                    ->flatMap(fn ($ingredient) => $warnings[$ingredient->food_id] ?? [])
                                    ->unique('text');
                                $dishIngredients = $dish->items->map->displayName()->join(', ');
                            @endphp

                            <button type="button"
                                    class="dp-dish-card-item"
                                    role="option"
                                    aria-selected="false"
                                    data-dish-id="{{ $dish->id }}"
                                    data-name="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($dish->name.' '.$dishIngredients)) }}"
                                    @disabled($dishEmpty)>
                                <span class="dp-dish-card-thumb">
                                    @if($dish->image_url)
                                        <img src="{{ $dish->imageSrc() }}" alt="" loading="lazy">
                                    @else
                                        <i class="fas fa-utensils"></i>
                                    @endif
                                </span>

                                <span class="dp-dish-card-info">
                                    <strong>{{ $dish->name }}</strong>
                                    <span>
                                        {{ $dish->isSystem() ? 'Receta recomendada' : 'Mi platillo' }}
                                        @unless($dishEmpty)
                                            · {{ $dish->items->count() }} {{ $dish->items->count() === 1 ? 'ingrediente' : 'ingredientes' }}
                                        @endunless
                                    </span>

                                    @unless($dishEmpty)
                                        <span class="dp-dish-nutrients">
                                            <b>{{ $fmt($dishTotals['kcal']) }} kcal</b>
                                            <i>P {{ $fmt($dishTotals['protein_g'], 1) }}</i>
                                            <i>C {{ $fmt($dishTotals['carbs_g'], 1) }}</i>
                                            <i>G {{ $fmt($dishTotals['lipids_g'], 1) }}</i>
                                        </span>
                                        <span class="dp-dish-ingredients">{{ $dishIngredients }}</span>
                                    @endunless

                                    @if($dishEmpty)
                                        <span class="dp-warning is-limited">
                                            <i class="fas fa-ban" aria-hidden="true"></i>
                                            Sin ingredientes registrados: no se puede agregar
                                        </span>
                                    @elseif($dishMissing > 0)
                                        <span class="dp-warning is-limited">
                                            <i class="fas fa-question-circle" aria-hidden="true"></i>
                                            {{ $dishMissing }} {{ $dishMissing === 1 ? 'ingrediente' : 'ingredientes' }} sin datos nutricionales
                                        </span>
                                    @endif

                                    @foreach($dishWarnings as $warning)
                                        <span class="dp-warning is-{{ $warning['type'] }}">
                                            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                                            {{ $warning['text'] }}
                                        </span>
                                    @endforeach
                                </span>

                                <span class="dp-dish-card-check" aria-hidden="true">
                                    <i class="fas fa-check-circle"></i>
                                </span>
                            </button>
                        @endforeach

                        <p class="dp-no-results" id="dishNoResults" hidden>Ningún platillo coincide con la búsqueda.</p>
                    </div>
                </div>

                <div class="dp-custom-warning">
                    <i class="fas fa-circle-info"></i>
                    <span>
                        Se insertarán todos los ingredientes desglosados de este platillo dentro de
                        <strong data-meal-label>la comida</strong>. Después puedes ajustar o quitar cada uno;
                        cambiar el platillo no modifica este plan.
                    </span>
                </div>

                <div class="dp-drawer-actions">
                    <button type="submit" class="dp-drawer-submit" id="dishSubmit" data-loading-text="Insertando…" disabled>
                        <i class="fas fa-plus"></i>
                        Insertar platillo seleccionado
                    </button>
                </div>
            </form>
        </aside>
    </div>


    {{-- =====================================================
         DRAWER: AGREGAR ALIMENTO (catálogo) + ALIMENTO MANUAL
         ===================================================== --}}
    @php
        $foodOld = in_array($reopenDrawer, ['food', 'custom'], true);
        $customOld = $reopenDrawer === 'custom';
    @endphp
    <div class="dp-drawer-overlay"
         id="foodDrawer"
         data-dp-drawer="food"
         @if($foodOld) data-reopen-meal="{{ $reopenMeal }}" data-reopen-panel="{{ $reopenDrawer }}" data-reopen-key="{{ old('_drawer_key') }}" @endif
         aria-hidden="true">
        <aside class="dp-drawer" role="dialog" aria-modal="true" aria-labelledby="foodDrawerTitle">

            {{-- Modos (data-mode del botón que lo abre): agregar a la comida,
                 agregar ingrediente a un platillo o sustituir un ingrediente. --}}
            <div class="dp-drawer-header">
                <div>
                    <span data-drawer-kicker>AGREGAR ALIMENTO</span>
                    <h3 id="foodDrawerTitle" data-drawer-title>la comida</h3>
                </div>
                <button type="button" data-dp-close aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- ALIMENTO DEL CATÁLOGO --}}
            <form action="" method="POST" class="dp-drawer-form" data-dp-form="food" id="foodForm">
                @csrf
                <input type="hidden" name="_drawer" value="food">
                <input type="hidden" name="_drawer_meal" value="" data-meal-input>
                @include('diet-plans.partials.drawer-mode-inputs')

                <div class="dp-drawer-field">
                    <label for="foodSearch">
                        Buscar alimento
                        <a href="{{ route('foods.index') }}" target="_blank" rel="noopener">
                            Ver catálogo completo <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                            <span class="visually-hidden">(se abre en otra pestaña)</span>
                        </a>
                    </label>
                    <div class="dp-drawer-search">
                        <i class="fas fa-search"></i>
                        <input type="search"
                               id="foodSearch"
                               class="dp-search-input"
                               placeholder="Ej. pollo, arroz, avena..."
                               autocomplete="off">
                    </div>

                    @if($foodGroups->count() > 1)
                        <label for="foodGroupSelect" class="visually-hidden">Grupo de alimentos</label>
                        <select id="foodGroupSelect" class="dp-food-select nl-mt-8 nl-h-40">
                            <option value="">Todos los grupos</option>
                            @foreach($foodGroups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    @endif

                    <small class="dp-search-count" id="foodResultCount" aria-live="polite">
                        {{ $foods->count() }} {{ $foods->count() === 1 ? 'alimento' : 'alimentos' }}
                    </small>
                </div>

                <div class="dp-drawer-field">
                    <label for="foodSelect">Selecciona un alimento</label>

                    <select name="food_id" id="foodSelect" class="dp-food-select" required>
                        <option value="">Seleccionar alimento...</option>

                        @foreach($foods as $food)
                            @php $foodWarnings = $warnings[$food->id] ?? []; @endphp
                            <option value="{{ $food->id }}"
                                    data-name="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($food->name.' '.($food->foodGroup?->name ?? ''))) }}"
                                    data-label="{{ $food->name }}"
                                    data-group="{{ $food->food_group_id }}"
                                    data-kcal="{{ $food->kcal }}"
                                    data-protein="{{ $food->protein_g }}"
                                    data-carbs="{{ $food->carbs_g }}"
                                    data-fat="{{ $food->lipids_g }}"
                                    data-portion="{{ $food->portion_grams }}"
                                    data-portion-description="{{ $food->portion_description ?? '1 porción' }}"
                                    data-warnings="{{ json_encode($foodWarnings, JSON_UNESCAPED_UNICODE) }}"
                                    @selected($foodOld && ! $customOld && (string) old('food_id') === (string) $food->id)>{{ $food->name }}@if($foodWarnings) — ⚠ {{ collect($foodWarnings)->pluck('text')->join('; ') }}@endif</option>
                        @endforeach
                    </select>
                </div>

                {{-- FORMA DE MEDIR --}}
                <div class="dp-drawer-field">
                    <span class="dp-drawer-label">Forma de medir</span>

                    <div class="dp-measure-selector" role="group" aria-label="Forma de medir">
                        <button type="button" class="dp-measure-option is-active" data-measure="portion" aria-pressed="true">
                            <i class="fas fa-egg"></i>
                            Porciones
                        </button>
                        <button type="button" class="dp-measure-option" data-measure="grams" aria-pressed="false">
                            <i class="fas fa-weight-scale"></i>
                            Gramos
                        </button>
                    </div>
                </div>

                {{-- CANTIDAD --}}
                <div class="dp-drawer-field">
                    <label for="foodQuantity">Cantidad</label>

                    <div class="dp-quantity-control">
                        <button type="button" data-quantity-step="-1" aria-label="Menos">
                            <i class="fas fa-minus"></i>
                        </button>

                        <div class="dp-quantity-input-wrapper">
                            <input type="number"
                                   id="foodQuantity"
                                   value="1"
                                   min="0.5"
                                   step="0.5"
                                   inputmode="decimal">
                            <span id="foodQuantityUnit">porciones</span>
                        </div>

                        <button type="button" data-quantity-step="1" aria-label="Más">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>

                    <div class="dp-portion-equivalence" id="portionEquivalence">Selecciona un alimento.</div>

                    {{-- Lo que se guarda: gramos (el servidor calcula los equivalentes) --}}
                    <input type="hidden" name="grams" id="foodGrams" value="{{ $foodOld && ! $customOld ? old('grams') : '' }}">
                </div>

                {{-- PREVIEW --}}
                <div class="dp-food-preview" id="foodPreview" aria-live="polite">
                    <div class="dp-preview-placeholder">
                        <i class="fas fa-apple-whole"></i>
                        <span>Selecciona un alimento para ver sus valores nutricionales.</span>
                    </div>
                </div>

                <div class="dp-drawer-actions">
                    <button type="submit" class="dp-drawer-submit" data-loading-text="Guardando…" @disabled($foods->isEmpty())>
                        <i class="fas fa-plus"></i>
                        <span data-submit-label>Agregar a <span data-meal-label>la comida</span></span>
                    </button>
                </div>
            </form>

            {{-- CAMBIAR A ALIMENTO MANUAL --}}
            <div class="dp-custom-food-divider" id="customFoodDivider">
                <span>¿No encuentras el alimento?</span>
                <button type="button" data-dp-custom="show">
                    <i class="fas fa-pen"></i>
                    Agregar manualmente
                </button>
            </div>

            {{-- ALIMENTO PERSONALIZADO (food_id NULL; se guarda el aporte de la cantidad) --}}
            <form action="" method="POST" class="dp-drawer-form" data-dp-form="custom" id="customFoodForm" hidden>
                @csrf
                <input type="hidden" name="_drawer" value="custom">
                <input type="hidden" name="_drawer_meal" value="" data-meal-input>
                @include('diet-plans.partials.drawer-mode-inputs')

                <div class="dp-custom-form-header">
                    <button type="button" data-dp-custom="hide" aria-label="Volver al catálogo">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <div>
                        <span>ALIMENTO PERSONALIZADO</span>
                        <h4 data-custom-title>Agregar a <span data-meal-label>la comida</span></h4>
                    </div>
                </div>

                <div class="dp-custom-warning">
                    <i class="fas fa-circle-info"></i>
                    <span>
                        Este alimento solamente se guardará dentro de este plan y no será
                        añadido al catálogo general de NutriLife. No se revisan alergias ni patologías.
                    </span>
                </div>

                <div class="dp-drawer-field">
                    <label for="customName">Nombre del alimento</label>
                    <input type="text"
                           name="custom_name"
                           id="customName"
                           class="dp-custom-input"
                           placeholder="Ej. Salsa casera de aguacate"
                           maxlength="255"
                           value="{{ $customOld ? old('custom_name') : '' }}"
                           required>
                </div>

                <div class="dp-drawer-field">
                    <label for="customGrams">Cantidad utilizada</label>
                    <div class="dp-unit-row">
                        <input type="number"
                               name="grams"
                               id="customGrams"
                               class="dp-custom-input"
                               placeholder="150"
                               min="0.1"
                               max="{{ \App\Models\DietPlanItem::MAX_GRAMS }}"
                               step="0.01"
                               inputmode="decimal"
                               value="{{ $customOld ? old('grams') : '' }}"
                               required>
                        <span>g</span>
                    </div>
                </div>

                <div class="dp-drawer-field">
                    <label for="customPortion">Descripción de la porción <small>(opcional)</small></label>
                    <input type="text"
                           name="custom_portion_description"
                           id="customPortion"
                           class="dp-custom-input"
                           placeholder="Ej. 1 plato, 1 taza, 2 piezas..."
                           maxlength="255"
                           value="{{ $customOld ? old('custom_portion_description') : '' }}">
                </div>

                <div class="dp-drawer-field">
                    <label for="customKcal">Calorías de esa cantidad</label>
                    <div class="dp-unit-row">
                        <input type="number"
                               name="custom_kcal"
                               id="customKcal"
                               class="dp-custom-input"
                               placeholder="320"
                               min="0"
                               max="{{ \App\Models\DietPlan::MAX_KCAL }}"
                               step="0.01"
                               inputmode="decimal"
                               value="{{ $customOld ? old('custom_kcal') : '' }}"
                               required>
                        <span>kcal</span>
                    </div>
                </div>

                <div class="dp-custom-macro-grid">
                    @foreach(['custom_protein_g' => ['Proteína', '25'], 'custom_carbs_g' => ['Carbohidratos', '30'], 'custom_lipids_g' => ['Grasas', '12']] as $field => [$label, $placeholder])
                        <div>
                            <label for="{{ $field }}">{{ $label }}</label>
                            <div class="dp-unit-row">
                                <input type="number"
                                       name="{{ $field }}"
                                       id="{{ $field }}"
                                       class="dp-custom-input"
                                       placeholder="{{ $placeholder }}"
                                       min="0"
                                       max="{{ \App\Models\DietPlan::MAX_MACRO_G }}"
                                       step="0.01"
                                       inputmode="decimal"
                                       value="{{ $customOld ? old($field) : '' }}"
                                       required>
                                <span>g</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="dp-drawer-actions">
                    <button type="submit" class="dp-drawer-submit" data-loading-text="Guardando…">
                        <i class="fas fa-plus"></i>
                        <span data-custom-submit-label>Agregar alimento personalizado</span>
                    </button>
                </div>
            </form>
        </aside>
    </div>


    {{-- =====================================================
         DRAWER: DUPLICAR / ASIGNAR A OTRO PACIENTE
         ===================================================== --}}
    @php $duplicateOld = $reopenDrawer === 'duplicate'; @endphp
    <div class="dp-drawer-overlay"
         id="duplicateDrawer"
         data-dp-drawer="duplicate"
         @if($duplicateOld) data-reopen="1" @endif
         aria-hidden="true">
        <aside class="dp-drawer" role="dialog" aria-modal="true" aria-labelledby="duplicateTitle">

            <div class="dp-drawer-header">
                <div>
                    <span>REUTILIZAR ESTRUCTURA</span>
                    <h3 id="duplicateTitle">Duplicar este plan</h3>
                </div>
                <button type="button" data-dp-close aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form action="{{ route('diet-plans.duplicate', $plan) }}" method="POST" class="dp-drawer-form">
                @csrf
                <input type="hidden" name="_drawer" value="duplicate">

                <div class="dp-custom-warning">
                    <i class="fas fa-circle-info"></i>
                    <span>Se copian los objetivos, las indicaciones, las comidas y sus alimentos. El plan actual no cambia.</span>
                </div>

                <div class="dp-drawer-field">
                    <label for="duplicatePatient">Paciente</label>
                    <select name="patient_id" id="duplicatePatient" class="dp-food-select" required>
                        @foreach($patients as $option)
                            <option value="{{ $option->id }}" @selected((int) ($duplicateOld ? old('patient_id') : $plan->patient_id) === $option->id)>
                                {{ $option->first_name }} {{ $option->last_name }}{{ $option->id === $plan->patient_id ? ' (mismo paciente)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="dp-drawer-field">
                    <label for="duplicateName">Nombre del nuevo plan</label>
                    <input type="text"
                           name="name"
                           id="duplicateName"
                           class="dp-custom-input"
                           maxlength="255"
                           required
                           value="{{ $duplicateOld ? old('name') : \Illuminate\Support\Str::limit($plan->name.' (copia)', 255, '') }}">
                </div>

                <div class="dp-drawer-field">
                    <label for="duplicateStart">Fecha de inicio</label>
                    <input type="date"
                           name="starts_at"
                           id="duplicateStart"
                           class="dp-custom-input"
                           required
                           min="2000-01-01"
                           max="2100-12-31"
                           value="{{ $duplicateOld ? old('starts_at') : now()->format('Y-m-d') }}">
                </div>

                @if($plan->active)
                    <div class="dp-drawer-field">
                        <label class="dp-switch nl-justify-start" for="finishOriginal">
                            <input type="checkbox" name="finish_original" value="1" id="finishOriginal">
                            <span class="dp-switch-track" aria-hidden="true"></span>
                            <span>Si es para el mismo paciente, finalizar el plan actual</span>
                        </label>
                    </div>
                @endif

                @if($patients->isEmpty())
                    <p class="dp-warning is-forbidden">No tienes pacientes activos para asignar el plan.</p>
                @endif

                <div class="dp-drawer-actions">
                    <button type="submit" class="dp-drawer-submit" data-loading-text="Duplicando…" @disabled($patients->isEmpty())>
                        <i class="far fa-copy"></i>
                        Crear copia
                    </button>
                </div>
            </form>
        </aside>
    </div>

</div>
@stop

{{-- =========================================================
     CSS / JS
     ========================================================= --}}
@section('css')
    @include('diet-plans.partials.styles')
@stop

@section('js')
    @vite(['resources/js/pages/diet-plans.js'])
@stop
