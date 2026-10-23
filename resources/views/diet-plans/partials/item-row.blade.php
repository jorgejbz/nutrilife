{{-- =========================================================
     RENGLÓN DE ALIMENTO DE UNA COMIDA (diet_plan_items)
     Variables: $plan, $meal, $item y $inDish (true = ingrediente
     de un platillo: acciones con texto y opción «Sustituir»).
     Usa $warnings, $fmt, $trimNumber y $hasClinicalFlags de show.
     ========================================================= --}}
@php
    $nutrients = $item->nutrients();
    $itemName = $item->displayName();
    $itemWarnings = $item->food_id ? ($warnings[$item->food_id] ?? []) : [];
    $itemImage = $inDish ? null : $item->imageSrc();
    $gramsOpen = old('_grams_item') == $item->id && $errors->has('grams');
    $canUsePortions = ! $item->isCustom() && (float) ($item->food?->portion_grams ?? 0) > 0;
    $missingData = ! $item->hasNutrients()
        || (! $item->isCustom() && (float) ($item->food?->portion_grams ?? 0) <= 0);
@endphp

<li class="dp-food-row{{ $inDish ? ' is-ingredient' : '' }}">
    <div class="dp-food-row-main">
        <div class="dp-food-row-icon">
            @if($itemImage)
                <img src="{{ $itemImage }}" alt="" loading="lazy">
            @else
                <i class="fas fa-apple-whole" aria-hidden="true"></i>
            @endif
        </div>

        <div class="dp-food-info">
            <strong title="{{ $itemName }}">{{ $itemName }}</strong>

            <span>
                {{ $fmt((float) $item->grams) }} g
                @if($item->isCustom())
                    @if($item->custom_portion_description)
                        · {{ $item->custom_portion_description }}
                    @endif
                    · <span class="dp-badge">Personalizado</span>
                @else
                    · {{ $trimNumber($item->equivalents) }} {{ (float) $item->equivalents === 1.0 ? 'porción' : 'porciones' }}
                    @if($item->food?->portion_description)
                        de {{ $item->food->portion_description }}
                    @endif
                @endif
                @if(! $inDish && $item->dish)
                    · <i class="fas fa-utensils" aria-hidden="true"></i> {{ $item->dish->name }}
                @endif
            </span>

            @foreach($itemWarnings as $warning)
                <span class="dp-warning is-{{ $warning['type'] }}">
                    <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                    {{ $warning['text'] }}
                </span>
            @endforeach

            @if($missingData)
                <span class="dp-warning is-limited">
                    <i class="fas fa-question-circle" aria-hidden="true"></i>
                    Sin datos nutricionales completos (cuenta 0)
                </span>
            @endif

            @if($item->isCustom() && $hasClinicalFlags)
                <span class="dp-warning is-info">
                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                    Fuera del catálogo: no se revisan alergias ni patologías
                </span>
            @endif
        </div>
    </div>

    <div class="dp-food-row-values">
        <strong>{{ $fmt($nutrients['kcal']) }} kcal</strong>
        <span>
            {{ $fmt($nutrients['protein_g'], 1) }}P
            <b>·</b>
            {{ $fmt($nutrients['carbs_g'], 1) }}C
            <b>·</b>
            {{ $fmt($nutrients['lipids_g'], 1) }}G
        </span>
    </div>

    <div class="dp-food-row-actions">
        <button type="button"
                class="{{ $inDish ? 'dp-row-action' : 'dp-icon-btn' }}"
                title="Cambiar cantidad"
                aria-label="Cambiar cantidad de {{ $itemName }}"
                aria-controls="gramsForm{{ $item->id }}"
                aria-expanded="{{ $gramsOpen ? 'true' : 'false' }}"
                data-dp-toggle="gramsForm{{ $item->id }}">
            <i class="fas fa-pen" aria-hidden="true"></i>
            @if($inDish)<span>Cantidad</span>@endif
        </button>

        @if($inDish)
            {{-- SUSTITUIR: abre el drawer de alimento en modo sustitución --}}
            <button type="button"
                    class="dp-row-action"
                    title="Sustituir por otro alimento"
                    aria-label="Sustituir {{ $itemName }}"
                    data-dp-open="food"
                    data-mode="substitute"
                    data-dp-key="sub-{{ $item->id }}"
                    data-meal-id="{{ $meal->id }}"
                    data-meal-name="{{ $meal->meal_name }}"
                    data-item-name="{{ $itemName }}"
                    data-grams="{{ $trimNumber($item->grams) }}"
                    data-action="{{ route('diet-plans.items.substitute', [$plan, $meal, $item]) }}"
                    data-custom-action="{{ route('diet-plans.items.substitute', [$plan, $meal, $item]) }}">
                <i class="fas fa-right-left" aria-hidden="true"></i>
                <span>Sustituir</span>
            </button>
        @endif

        <form action="{{ route('diet-plans.items.destroy', [$plan, $meal, $item]) }}"
              method="POST"
              data-confirm="¿Quitar {{ $itemName }} de {{ $inDish && $item->dish ? '«'.$item->dish->name.'»' : $meal->meal_name }}? La receta original no cambia."
              data-confirm-title="{{ $inDish ? 'Quitar ingrediente' : 'Eliminar alimento' }}"
              data-confirm-accept="{{ $inDish ? 'Quitar' : 'Eliminar' }}"
              data-confirm-variant="danger"
              data-confirm-icon="fa-trash-alt">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="{{ $inDish ? 'dp-row-action is-danger' : 'dp-icon-btn is-danger' }}"
                    title="{{ $inDish ? 'Quitar ingrediente' : 'Eliminar alimento' }}"
                    aria-label="{{ $inDish ? 'Quitar' : 'Eliminar' }} {{ $itemName }}"
                    data-loading-text="">
                <i class="far fa-trash-alt" aria-hidden="true"></i>
                @if($inDish)<span>Quitar</span>@endif
            </button>
        </form>
    </div>

    {{-- CAMBIAR CANTIDAD (PATCH). Del catálogo: gramos o porciones; en personalizados reescala su aporte. --}}
    <form action="{{ route('diet-plans.items.update', [$plan, $meal, $item]) }}"
          method="POST"
          class="dp-grams-form"
          id="gramsForm{{ $item->id }}"
          @unless($gramsOpen) hidden @endunless>
        @csrf
        @method('PATCH')
        <input type="hidden" name="_grams_item" value="{{ $item->id }}">

        <label for="grams-{{ $item->id }}">Cantidad</label>
        <div class="dp-unit-row">
            <input type="number"
                   id="grams-{{ $item->id }}"
                   name="grams"
                   class="dp-custom-input"
                   value="{{ $gramsOpen ? old('grams') : $trimNumber($item->grams) }}"
                   min="0.01"
                   max="{{ \App\Models\DietPlanItem::MAX_GRAMS }}"
                   step="any"
                   inputmode="decimal"
                   data-grams-value="{{ $trimNumber($item->grams) }}"
                   data-portions-value="{{ $trimNumber($item->equivalents) }}"
                   required>
            @if($canUsePortions)
                <label for="unit-{{ $item->id }}" class="visually-hidden">Unidad</label>
                <select id="unit-{{ $item->id }}" name="unit" class="dp-unit-select" data-dp-unit>
                    <option value="g" @selected(! $gramsOpen || old('unit', 'g') === 'g')>g</option>
                    <option value="porciones" @selected($gramsOpen && old('unit') === 'porciones')>porciones{{ $item->food?->portion_description ? ' ('.$item->food->portion_description.')' : '' }}</option>
                </select>
            @else
                <span>g</span>
            @endif
        </div>
        <button type="button" class="dp-btn-secondary" data-dp-toggle="gramsForm{{ $item->id }}">Cancelar</button>
        <button type="submit" class="dp-btn-primary" data-loading-text="Guardando…">
            <i class="fas fa-check"></i>
            Guardar
        </button>
        @if($item->isCustom())
            <small>El aporte del alimento personalizado se ajusta en proporción a los gramos.</small>
        @elseif($canUsePortions)
            <small>1 porción = {{ $trimNumber($item->food->portion_grams) }} g{{ $item->food->portion_description ? ' ('.$item->food->portion_description.')' : '' }}.</small>
        @endif
    </form>
</li>
