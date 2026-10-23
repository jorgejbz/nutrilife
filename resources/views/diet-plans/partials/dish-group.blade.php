{{-- =========================================================
     PLATILLO DENTRO DE UNA COMIDA
     Sus ingredientes son COPIAS en diet_plan_items (ligadas por
     diet_plan_dish_id): cambiarlos aquí solo afecta este plan; la
     receta del catálogo y los planes de otros pacientes no cambian.
     Variables: $plan, $meal, $dishId, $dishItems.
     ========================================================= --}}
@php
    $first = $dishItems->first();
    $sourceDish = $first->dish;
    $dishName = $sourceDish?->name ?? 'Platillo';
    $dishImage = $first->imageSrc();
    $dishTotals = \App\Models\DietPlanItem::sumNutrients($dishItems->map->nutrients());
    $dishMissing = $dishItems->filter(fn ($row) => ! $row->hasNutrients()
        || (! $row->isCustom() && (float) ($row->food?->portion_grams ?? 0) <= 0))->count();
    $dishCustomized = $sourceDish?->differsFrom($dishItems) ?? false;
    $groupKey = $meal->id.'-'.$dishId;
    $scaleOpen = old('_scale_dish') === $groupKey && $errors->has('factor');
    $ingredientCount = $dishItems->count();
@endphp

<li class="dp-dish-group" id="platillo-{{ $groupKey }}" aria-labelledby="dish-title-{{ $groupKey }}">

    <div class="dp-dish-group-header">
        <div class="dp-food-row-main">
            <div class="dp-food-row-icon">
                @if($dishImage)
                    <img src="{{ $dishImage }}" alt="" loading="lazy">
                @else
                    <i class="fas fa-utensils" aria-hidden="true"></i>
                @endif
            </div>

            <div class="dp-food-info">
                <span class="dp-dish-group-kicker"><i class="fas fa-utensils" aria-hidden="true"></i> PLATILLO</span>
                <strong id="dish-title-{{ $groupKey }}" title="{{ $dishName }}">{{ $dishName }}</strong>
                <span>
                    {{ $ingredientCount }} {{ $ingredientCount === 1 ? 'ingrediente' : 'ingredientes' }}
                    @if($dishCustomized)
                        · <span class="dp-badge">Personalizado para este paciente</span>
                    @endif
                </span>
            </div>
        </div>

        <div class="dp-food-row-values">
            <strong>{{ $dishMissing > 0 ? '≥ ' : '' }}{{ $fmt($dishTotals['kcal']) }} kcal</strong>
            <span>
                {{ $fmt($dishTotals['protein_g'], 1) }}P
                <b>·</b>
                {{ $fmt($dishTotals['carbs_g'], 1) }}C
                <b>·</b>
                {{ $fmt($dishTotals['lipids_g'], 1) }}G
            </span>
        </div>

        <div class="dp-food-row-actions">
            <button type="button"
                    class="dp-icon-btn"
                    title="Mostrar u ocultar ingredientes"
                    aria-label="Mostrar u ocultar ingredientes de {{ $dishName }}"
                    aria-controls="dishItems{{ $groupKey }}"
                    aria-expanded="true"
                    data-dp-toggle="dishItems{{ $groupKey }}">
                <i class="fas fa-list-ul" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    @if($dishMissing > 0)
        <p class="dp-dish-group-note">
            <span class="dp-warning is-limited">
                <i class="fas fa-question-circle" aria-hidden="true"></i>
                {{ $dishMissing }} {{ $dishMissing === 1 ? 'ingrediente' : 'ingredientes' }} sin datos nutricionales: el total del platillo puede ser mayor.
            </span>
        </p>
    @endif

    <div class="dp-dish-group-body" id="dishItems{{ $groupKey }}">
        <ul class="dp-dish-group-items" aria-label="Ingredientes de {{ $dishName }}">
            @foreach($dishItems as $dishItem)
                @include('diet-plans.partials.item-row', ['item' => $dishItem, 'inDish' => true])
            @endforeach
        </ul>

        <div class="dp-dish-group-tools">
            <button type="button"
                    class="dp-btn"
                    data-dp-open="food"
                    data-mode="dish"
                    data-dp-key="dish-{{ $groupKey }}"
                    data-dish-id="{{ $dishId }}"
                    data-dish-name="{{ $dishName }}"
                    data-meal-id="{{ $meal->id }}"
                    data-meal-name="{{ $meal->meal_name }}"
                    data-action="{{ route('diet-plans.items.store', [$plan, $meal]) }}"
                    data-custom-action="{{ route('diet-plans.items.custom.store', [$plan, $meal]) }}">
                <i class="fas fa-plus" aria-hidden="true"></i>
                Agregar ingrediente
            </button>

            <button type="button"
                    class="dp-btn-secondary"
                    aria-controls="scaleForm{{ $groupKey }}"
                    aria-expanded="{{ $scaleOpen ? 'true' : 'false' }}"
                    data-dp-toggle="scaleForm{{ $groupKey }}">
                <i class="fas fa-scale-balanced" aria-hidden="true"></i>
                Ajustar porciones
            </button>
        </div>

        {{-- PORCIONES: multiplica las cantidades de todos los ingredientes --}}
        <form action="{{ route('diet-plans.meals.dishes.scale', [$plan, $meal, $dishId]) }}"
              method="POST"
              class="dp-grams-form"
              id="scaleForm{{ $groupKey }}"
              @unless($scaleOpen) hidden @endunless>
            @csrf
            @method('PATCH')
            <input type="hidden" name="_scale_dish" value="{{ $groupKey }}">

            <label for="factor-{{ $groupKey }}">Multiplicar cantidades por</label>
            <div class="dp-unit-row">
                <input type="number"
                       id="factor-{{ $groupKey }}"
                       name="factor"
                       class="dp-custom-input"
                       value="{{ $scaleOpen ? old('factor') : '1' }}"
                       min="0.1"
                       max="10"
                       step="0.01"
                       inputmode="decimal"
                       required>
                <span>×</span>
            </div>
            <button type="button" class="dp-btn-secondary" data-dp-toggle="scaleForm{{ $groupKey }}">Cancelar</button>
            <button type="submit" class="dp-btn-primary" data-loading-text="Guardando…">
                <i class="fas fa-check"></i>
                Aplicar
            </button>
            <small>Ej. 0.5 = media porción, 2 = doble. Se recalculan gramos, porciones, calorías y macros de cada ingrediente.</small>
        </form>
    </div>
</li>
