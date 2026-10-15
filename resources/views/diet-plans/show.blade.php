@extends('adminlte::page')

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
                        {{-- =====================================================
     CAMBIAR A ALIMENTO MANUAL
===================================================== --}}

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



{{-- =====================================================
     FORMULARIO ALIMENTO MANUAL
===================================================== --}}

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



    {{-- NOMBRE --}}

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



    {{-- CANTIDAD --}}

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



    {{-- DESCRIPCIÓN --}}

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



    {{-- CALORÍAS --}}

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



    {{-- MACROS --}}

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



                               {{-- GRAMOS --}}

{{-- =====================================================
     FORMA DE MEDIR
===================================================== --}}

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



{{-- =====================================================
     CANTIDAD VISIBLE
===================================================== --}}

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



    {{-- Este es el valor REAL que Laravel recibe --}}

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

{{-- Paleta y componentes compartidos de NutriLife --}}
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


/* =========================================================
   GENERAL
========================================================= */

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


/* =========================================================
   ADMINLTE NAVBAR
========================================================= */

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


/* =========================================================
   HEADER
========================================================= */

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


/* =========================================================
   ALERT
========================================================= */

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


/* =========================================================
   DAILY SUMMARY
========================================================= */

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


/* =========================================================
   MACROS
========================================================= */

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


/* =========================================================
   MEALS SECTION
========================================================= */

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


/* =========================================================
   BUTTONS
========================================================= */

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


/* =========================================================
   NEW MEAL
========================================================= */

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


/* =========================================================
   GRID COMIDAS
========================================================= */

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


/* =========================================================
   MEAL HEADER
========================================================= */

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


/* =========================================================
   EMPTY MEAL
========================================================= */

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


/* =========================================================
   FOOD LIST
========================================================= */

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


/* =========================================================
   MEAL FOOTER
========================================================= */

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


/* =========================================================
   NO MEALS
========================================================= */

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


/* =========================================================
   DRAWER OVERLAY
========================================================= */

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


/* =========================================================
   DRAWER
========================================================= */

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


/* =========================================================
   GRAMS CONTROL
========================================================= */

.grams-control {

    display:
        grid;

    grid-template-columns:
        43px 1fr 43px;

    gap:
        8px;

}


.grams-control > button {

    height:
        44px;

    border:
        1px solid rgba(255,255,255,.08);

    border-radius:
        11px;

    background:
        rgba(255,255,255,.035);

    color:
        #b6d4b0;

    cursor:
        pointer;

}


.grams-control > div {

    display:
        flex;

    align-items:
        center;

    border:
        1px solid rgba(255,255,255,.09);

    border-radius:
        11px;

    background:
        rgba(255,255,255,.04);

}


.grams-control input {

    width:
        100%;

    height:
        42px;

    padding:
        0 10px;

    border:
        0;

    outline:
        0;

    background:
        transparent;

    color:
        white;

    font-size:
        13px;

    font-weight:
        600;

    text-align:
        center;

}


.grams-control span {

    padding-right:
        13px;

    color:
        rgba(214,232,217,.35);

    font-size:
        9px;

}


/* =========================================================
   PREVIEW
========================================================= */

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


/* =========================================================
   DRAWER SUBMIT
========================================================= */

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


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width: 1100px) {

    .daily-summary {

        grid-template-columns:
            1fr;

    }


    .daily-main {

        padding-right:
            0;

        padding-bottom:
            20px;

        border-right:
            0;

        border-bottom:
            1px solid rgba(255,255,255,.06);

    }

}


@media(max-width: 950px) {

    .meals-list {

        grid-template-columns:
            1fr;

    }

}


@media(max-width: 700px) {

    .builder-header,
    .meals-heading {

        align-items:
            flex-start;

        flex-direction:
            column;

    }


    .macro-progress-grid {

        grid-template-columns:
            1fr;

    }


    .new-meal-form form {

        align-items:
            stretch;

        flex-direction:
            column;

    }


    .meal-form-actions {

        width:
            100%;

    }


    .meal-form-actions button {

        flex:
            1;

    }


    .meal-header {

        align-items:
            flex-start;

        flex-direction:
            column;

    }


    .meal-header-right {

        width:
            100%;

    }


    .meal-header-kcal {

        margin-right:
            auto;

        text-align:
            left;

    }

}


@media(max-width: 520px) {

    .daily-summary {

        padding:
            19px;

    }


    .food-row {

        grid-template-columns:
            1fr 35px;

    }


    .food-row-values {

        grid-column:
            1 / -1;

        grid-row:
            2;

        padding-left:
            48px;

        text-align:
            left;

    }


    .meal-footer {

        align-items:
            flex-start;

        flex-direction:
            column;

    }


    .meal-footer-values {

        text-align:
            left;

    }


    /* =========================================================
   SELECTOR DE MEDICIÓN
========================================================= */

.measure-selector {

    display:
        grid;

    grid-template-columns:
        1fr 1fr;

    gap:
        8px;

}


.measure-option {

    height:
        43px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        7px;

    border:
        1px solid rgba(255,255,255,.08);

    border-radius:
        11px;

    background:
        rgba(255,255,255,.025);

    color:
        rgba(220,235,223,.40);

    font-size:
        9px;

    cursor:
        pointer;

    transition:
        .2s ease;

}


.measure-option:hover {

    border-color:
        rgba(169,207,162,.18);

    color:
        #b9d5b4;

}


.measure-option.active {

    border-color:
        rgba(169,210,161,.22);

    background:
        rgba(154,198,148,.10);

    color:
        #c2dfbc;

}



/* =========================================================
   CANTIDAD
========================================================= */

.quantity-control {

    display:
        grid;

    grid-template-columns:
        45px 1fr 45px;

    gap:
        8px;

}


.quantity-control > button {

    height:
        46px;

    border:
        1px solid rgba(255,255,255,.08);

    border-radius:
        12px;

    background:
        rgba(255,255,255,.035);

    color:
        #b9d8b3;

    font-size:
        11px;

    cursor:
        pointer;

    transition:
        .2s;

}


.quantity-control > button:hover {

    background:
        rgba(156,199,149,.10);

}


.quantity-input-wrapper {

    display:
        flex;

    align-items:
        center;

    border:
        1px solid rgba(255,255,255,.09);

    border-radius:
        12px;

    background:
        rgba(255,255,255,.04);

}


.quantity-input-wrapper input {

    width:
        100%;

    height:
        44px;

    padding:
        0 8px;

    border:
        0;

    outline:
        0;

    background:
        transparent;

    color:
        white;

    font-size:
        14px;

    font-weight:
        700;

    text-align:
        center;

}


.quantity-input-wrapper span {

    min-width:
        63px;

    padding-right:
        13px;

    color:
        rgba(216,232,219,.34);

    font-size:
        8px;

    text-align:
        right;

}



/* =========================================================
   EQUIVALENCIA
========================================================= */

.portion-equivalence {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        10px;

    margin-top:
        9px;

    padding:
        8px 10px;

    border-radius:
        9px;

    background:
        rgba(255,255,255,.025);

    color:
        rgba(214,231,217,.35);

    font-size:
        8px;

}


.portion-equivalence strong {

    color:
        #acd0a5;

    font-size:
        8px;

    font-weight:
        600;

}
}


/* =========================================================
   ALIMENTO PERSONALIZADO
========================================================= */

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


/* FORMULARIO */

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


/* AVISO */

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


/* INPUT */

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


/* INPUT CON UNIDAD */

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


/* MACROS */

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


@media(max-width: 520px) {

    .custom-macro-grid {

        grid-template-columns: 1fr;
    }

}


/* ==========================================
   NUTRILIFE - TONOS MÁS CLAROS
   CONSTRUCTOR DE PLANES ALIMENTICIOS
   Solo mejoras visuales
========================================== */

/* VARIABLES GENERALES */
:root {
    --nl-card: rgba(170, 210, 174, .075);
    --nl-border: rgba(173, 213, 176, .23);
    --nl-green: #c3e5b9;
    --nl-green-soft: #aad7ab;
    --nl-lime: #e9f39a;
    --nl-text: #f3fbf4;
    --nl-muted: rgba(225, 242, 228, .72);
}

/* ==========================================
   ENCABEZADO
========================================== */

.builder-breadcrumb {
    color: #c4e2c5 !important;
}

.builder-breadcrumb:hover {
    color: #e9fae6 !important;
}

.builder-eyebrow,
.section-eyebrow {
    color: #b6dcb5;
}

.builder-header p,
.meals-heading p {
    color: rgba(230, 245, 232, .78);
}

.plan-status {
    background: rgba(169, 208, 173, .13);
    border-color: rgba(169, 208, 173, .25);
    color: #d1e8d0;
}

.plan-status.active {
    background: rgba(139, 205, 148, .18);
    border-color: rgba(166, 224, 173, .35);
    color: #c7efc9;
}

/* ==========================================
   RESUMEN NUTRICIONAL
========================================== */

.daily-summary {
    background: linear-gradient(
        145deg,
        rgba(175, 213, 177, .12),
        rgba(175, 213, 177, .045)
    );

    border-color: rgba(171, 215, 176, .25);
}

.daily-main {
    border-right-color: rgba(183, 222, 188, .20);
}

.summary-label {
    color: #bbdcbf;
}

.daily-kcal span,
.daily-remaining,
.daily-remaining strong {
    color: #d4ebd2;
}

.daily-percentage {
    background: rgba(170, 219, 163, .20);
    color: #d8f1d1;
}

.progress-track {
    background: rgba(174, 212, 179, .17);
}

/* TARJETAS DE MACRONUTRIENTES */

.macro-progress-card {
    background: rgba(166, 206, 170, .10);
    border-color: rgba(171, 211, 175, .22);
}

.macro-card-top > span {
    color: #bedfc1;
}

.macro-current small,
.macro-percent {
    color: rgba(225, 241, 227, .75);
}

/* ==========================================
   TARJETAS DE COMIDAS
========================================== */

.meal-card {
    background: linear-gradient(
        145deg,
        rgba(170, 210, 174, .11),
        rgba(170, 210, 174, .045)
    );

    border-color: rgba(172, 212, 176, .25);
}

.meal-card:hover {
    border-color: rgba(184, 229, 188, .48);
}

.meal-header {
    border-bottom-color: rgba(177, 215, 179, .20);
}

.meal-icon,
.food-row-icon,
.meal-empty-icon,
.no-meals-icon {
    background: rgba(167, 215, 170, .18);
    color: #bfe5b9;
}

.meal-title span,
.meal-header-kcal span {
    color: rgba(224, 243, 226, .72);
}

/* ==========================================
   BOTONES PRINCIPALES
========================================== */

.add-meal-button,
.primary-button,
.drawer-submit {
    background: linear-gradient(
        135deg,
        #c3eabc,
        #e7f39a
    );

    color: #193720;

    border: 1px solid rgba(223, 247, 201, .4);

    box-shadow: 0 5px 18px rgba(163, 215, 150, .17);
}

.add-meal-button:hover,
.primary-button:hover,
.drawer-submit:hover {
    background: linear-gradient(
        135deg,
        #d5f5ce,
        #f1fac0
    );

    color: #112e19;

    box-shadow: 0 9px 25px rgba(170, 221, 155, .27);
}

/* ==========================================
   BOTONES SECUNDARIOS
========================================== */

.secondary-button,
.open-food-button,
.measure-option,
.quantity-control > button,
.custom-form-header > button {
    background: rgba(170, 215, 174, .17);
    border-color: rgba(180, 225, 183, .34);
    color: #d4f0d0;
}

.secondary-button:hover,
.open-food-button:hover,
.measure-option:hover,
.quantity-control > button:hover,
.custom-form-header > button:hover {
    background: rgba(175, 224, 180, .28);
    border-color: rgba(196, 237, 195, .55);
    color: #f0fff0;
}

/* BOTONES DE SELECCIÓN */

.measure-option.active {
    background: rgba(187, 232, 174, .28);
    border-color: #addba5;
    color: #efffea;
    box-shadow: 0 0 0 2px rgba(180, 225, 171, .08);
}

/* AGREGAR MANUALMENTE */

.custom-food-divider {
    border-top-color: rgba(176, 217, 181, .23);
}

.custom-food-divider span {
    color: #c4dfc5;
}

.custom-food-divider button {
    color: #d1efc8;
}

.custom-food-divider button:hover {
    color: #f0ffe7;
}

/* ==========================================
   BOTONES PARA ELIMINAR
========================================== */

.delete-meal-button,
.delete-food-button {
    background: rgba(225, 115, 115, .15);
    border-color: rgba(235, 146, 146, .30);
    color: #ffb3b3;
}

.delete-meal-button:hover,
.delete-food-button:hover {
    background: rgba(229, 105, 105, .25);
    border-color: rgba(245, 148, 148, .55);
    color: #ffe0e0;
}

/* ==========================================
   CAMPOS Y FORMULARIOS
========================================== */

.new-meal-form {
    background: rgba(164, 207, 168, .09);
    border-color: rgba(173, 216, 176, .24);
}

.meal-form-field label,
.drawer-field label,
.custom-macro-grid label {
    color: #d9eedb;
}

.meal-form-field input,
.food-search,
.food-select,
.custom-input,
.quantity-input-wrapper,
.custom-unit-row {
    background: rgba(165, 208, 168, .10);
    border-color: rgba(172, 215, 175, .29);
    color: #f3fbf3;
}

.meal-form-field input:focus,
.food-search:focus,
.food-select:focus,
.custom-input:focus,
.quantity-input-wrapper:focus-within,
.custom-unit-row:focus-within {
    border-color: #b4e0b0;
    box-shadow: 0 0 0 3px rgba(172, 222, 169, .13);
}

.meal-form-field input::placeholder,
.food-search::placeholder,
.custom-input::placeholder {
    color: rgba(225, 243, 228, .57);
}

.food-select option {
    background: #234232;
    color: #f3fbf3;
}

/* ==========================================
   PANEL LATERAL DE ALIMENTOS
========================================== */

.food-drawer {
    background:
        radial-gradient(
            circle at 20% 5%,
            rgba(118, 180, 127, .20),
            transparent 40%
        ),
        #173426;

    border-left-color: rgba(185, 222, 188, .25);
}

.drawer-search > i {
    color: #b9daba;
}

.drawer-header span {
    color: #bce0bb;
}

.custom-warning {
    background: rgba(206, 224, 144, .10);
    border-color: rgba(218, 231, 157, .25);
    color: #e2efc3;
}

.custom-unit-row span,
.quantity-input-wrapper span {
    color: #c4e1c6;
}

/* ==========================================
   LISTA DE ALIMENTOS
========================================== */

.food-row {
    border-color: rgba(170, 211, 173, .16);
}

.food-row-values span {
    color: #bdd9bd;
}

.meal-footer {
    border-color: rgba(175, 216, 179, .20);
}

/* ==========================================
   FORMULARIO MANUAL
========================================== */

.custom-form-header > button {
    background: rgba(177, 221, 178, .16);
}

.custom-form-header span {
    color: #bddfbc;
}

.custom-food-form .drawer-submit {
    background: linear-gradient(
        135deg,
        #bce6b5,
        #e5f092
    );
}

/* ==========================================
   ESTADOS VACÍOS
========================================== */

.no-meals,
.meal-empty {
    color: #e4f2e4;
}

.no-meals p,
.meal-empty span {
    color: #bfd9c1;
}

/* ==========================================
   AJUSTE RESPONSIVE
========================================== */

@media (max-width: 768px) {
    .daily-main {
        border-right-color: transparent;
    }

    .add-meal-button,
    .primary-button,
    .drawer-submit {
        font-weight: 700;
    }
}

/* =========================================================
   NUTRILIFE · MISMO LENGUAJE VISUAL QUE PLANES ALIMENTICIOS
   Estilos de presentación; no modifica lógica ni interacciones.
========================================================= */
:root {
    --nl-bg: #0b1911;
    --nl-card: #14251a;
    --nl-border: rgba(167,205,164,.20);
    --nl-green: #b4d6aa;
    --nl-green-soft: #a3c99f;
    --nl-lime: #e2e99a;
    --nl-text: #f1f7f0;
    --nl-muted: #bbcfbf;
}
body {
    background: radial-gradient(circle at 10% 5%, rgba(104,157,108,.15), transparent 35%),
                linear-gradient(135deg,#0c1d13,#10271a 55%,#0a1a12) !important;
    background-attachment: fixed !important;
}
.content-wrapper,.content-header { background: transparent !important; }
.main-header.navbar { background: #102319 !important; border-bottom: 1px solid rgba(167,205,164,.14) !important; }
.builder-header { align-items: center; gap: 22px; padding: 10px 0 18px; }
.builder-breadcrumb { color: #c1d8c3 !important; font-size: 12px; font-weight: 600; margin-bottom: 15px; }
.builder-breadcrumb:hover { color: #e1efdd !important; }
.builder-eyebrow,.section-eyebrow { color: #a9d5a7 !important; font-size: 10px; font-weight: 800; letter-spacing: 1.6px; }
.builder-header h1 { color: #f5faf4 !important; font-weight: 750; letter-spacing: -.5px; }
.builder-header p,.meals-heading p { color: #c1d2c2 !important; font-size: 12px; line-height: 1.7; }
.plan-status { color: #d6e1d7 !important; background: #213328 !important; border: 1px solid #415d46 !important; font-size: 11px; font-weight: 700; padding: 10px 14px; }
.plan-status.active { color: #c2f1c8 !important; background: #224b30 !important; border-color: #468258 !important; }
.builder-alert { border-radius: 13px; }
.builder-alert strong { font-size: 13px; }
.builder-alert span { font-size: 12px; opacity: 1; }
.builder-alert.success { color: #dbf5de; background: #1c4930; border-color: #417b51; }
.builder-alert.error { color: #ffe0df; background: #512e2c; border-color: #965b55; }
.daily-summary,.meal-card,.new-meal-form {
    background: linear-gradient(145deg,#1a3021,#15291c) !important;
    border: 1px solid rgba(170,209,165,.19) !important;
    border-radius: 18px !important;
    box-shadow: 0 12px 30px rgba(0,0,0,.12) !important;
}
.daily-summary { gap: 24px; padding: 26px; }
.daily-main { border-right: 1px solid rgba(170,209,165,.17) !important; }
.summary-label,.macro-card-top > span,.meal-title span,.section-eyebrow { color: #afcfb1 !important; font-size: 10px !important; }
.daily-kcal strong { color: #f5faf4 !important; }
.daily-kcal span,.daily-remaining,.daily-remaining strong { color: #c5d9c6 !important; font-size: 11px; }
.daily-remaining strong { color: #d7efce !important; }
.daily-percentage { background: #34543a !important; color: #e0f4d8 !important; font-size: 12px; }
.daily-percentage.over { background: #683c32 !important; color: #ffe1d4 !important; }
.progress-track { background: #364b3b !important; }
.progress-value { background: linear-gradient(90deg,#8fbd87,#d5e48c) !important; }
.progress-value.over { background: linear-gradient(90deg,#db8b72,#ebb36f) !important; }
.macro-progress-card { background: #213a28 !important; border-color: #3b5740 !important; border-radius: 13px; }
.macro-symbol { width: 32px; height: 32px; font-size: 11px; }
.macro-symbol.protein { color: #d4f3d4; background: #335c3e; }
.macro-symbol.carbs { color: #f1efba; background: #5d6037; }
.macro-symbol.fats { color: #c7ecf3; background: #315661; }
.macro-current strong { color: #f6faf5; font-size: 20px; }
.macro-current small,.macro-percent { color: #c1d7c4 !important; font-size: 10px; }
.meals-heading h2 { color: #f5faf4 !important; font-size: 26px; }
.add-meal-button,.primary-button,.drawer-submit {
    background: linear-gradient(135deg,#b5dba8,#dceaa8) !important;
    color: #183321 !important;
    border: 1px solid #d8ebbf !important;
    border-radius: 11px;
    font-size: 12px !important;
    font-weight: 800 !important;
    box-shadow: 0 4px 13px rgba(71,119,75,.14);
}
.add-meal-button:hover,.primary-button:hover,.drawer-submit:hover {
    background: #e1efc9 !important; color: #102719 !important;
    box-shadow: 0 8px 18px rgba(105,150,105,.22);
}
.secondary-button,.open-food-button,.measure-option,.quantity-control > button,.custom-form-header > button {
    background: #2c4b35 !important;
    border: 1px solid #648a69 !important;
    color: #e0f1de !important;
    font-size: 11px !important;
    font-weight: 700;
}
.secondary-button:hover,.open-food-button:hover,.measure-option:hover,.quantity-control > button:hover,.custom-form-header > button:hover {
    background: #426d4b !important; color: #fff !important;
}
.measure-option.active { background: #c4e1b8 !important; color: #1b3c25 !important; border-color: #e1f2d6 !important; }
.new-meal-form { padding: 20px; }
.meal-form-field label,.drawer-field label,.custom-macro-grid label { color: #e1ede0 !important; font-size: 12px; font-weight: 650; }
.meal-form-field input,.food-search,.food-select,.custom-input,.quantity-input-wrapper,.custom-unit-row {
    background: #243c2a !important; border: 1px solid #537359 !important;
    color: #f4faf4 !important; border-radius: 10px;
}
.meal-form-field input:focus,.food-search:focus,.food-select:focus,.custom-input:focus,.quantity-input-wrapper:focus-within,.custom-unit-row:focus-within {
    border-color: #b4dbac !important; box-shadow: 0 0 0 3px rgba(175,216,166,.16) !important; outline: none;
}
.meal-form-field input::placeholder,.food-search::placeholder,.custom-input::placeholder { color: #bbd0be !important; opacity: .8; }
.food-select option { color: #183021 !important; background: #e9f2e7 !important; }
.meal-card:hover { border-color: #7eae80 !important; }
.meal-header { background: rgba(119,164,119,.07); border-bottom: 1px solid #3a5942 !important; }
.meal-icon,.food-row-icon,.meal-empty-icon,.no-meals-icon { background: #35563d !important; color: #daefd5 !important; }
.meal-title h3,.meal-header-kcal strong,.food-info strong { color: #f5faf3 !important; }
.meal-header-kcal span { color: #c0d9bf !important; font-size: 10px; }
.delete-meal-button,.delete-food-button { background: #503434 !important; color: #ffd6d3 !important; border: 1px solid #865455 !important; }
.delete-meal-button:hover,.delete-food-button:hover { background: #784344 !important; color: #fff !important; }
.food-row { border-color: rgba(165,204,166,.15) !important; }
.food-info span,.food-row-values span { color: #c5d9c6 !important; font-size: 11px; }
.food-row-values strong,.meal-footer-values strong { color: #e8f8e3 !important; }
.meal-footer { background: #213928 !important; border-top: 1px solid #4b674f !important; }
.meal-footer span,.meal-footer > div > strong,.meal-footer-values span { color: #c5dec6 !important; }
.food-drawer-overlay { background: rgba(0,0,0,.65) !important; }
.food-drawer { background: #162a1d !important; border-left: 1px solid #456448 !important; box-shadow: -18px 0 55px rgba(0,0,0,.28) !important; }
.drawer-header { background: #203b29 !important; border-bottom: 1px solid #49684f !important; }
.drawer-header span,.custom-form-header span { color: #b9d7b7 !important; font-size: 10px; }
.drawer-header h3,.custom-form-header h4 { color: #f5faf2 !important; }
.drawer-header button { color: #ecf5e9 !important; }
.drawer-search > i { color: #c3d8c3 !important; }
.preview-placeholder,.food-preview-portion,.food-preview-macros span,.portion-equivalence { color: #c7dac8 !important; }
.food-preview,.food-preview-macros > div { background: #233c2b !important; border-color: #49684d !important; }
.food-preview-name,.food-preview-kcal,.food-preview-macros strong { color: #f0f7ed !important; }
.measure-selector,.quantity-control { border-color: #4b6e50 !important; }
.quantity-input-wrapper input { color: #f5faf2 !important; }
.custom-food-divider { border-color: #4b6b4e !important; }
.custom-food-divider span { color: #cbdcc9 !important; }
.custom-food-divider button { color: #d8f2cf !important; font-weight: 700; }
.custom-food-divider button:hover { color: #fff !important; }
.custom-food-form { background: #1b3323 !important; border-color: #577a5a !important; }
.custom-warning { background: #3a432e !important; color: #e6e8c0 !important; border: 1px solid #74774a !important; }
.custom-unit-row span,.quantity-input-wrapper span { color: #d7e9d7 !important; }
.no-meals,.meal-empty { color: #eef8ed !important; }
.no-meals p,.meal-empty span { color: #c4d9c3 !important; }
.no-meals > span { color: #a9d0a9 !important; }
@media (max-width: 768px) {
    .daily-main { border-right: 0 !important; border-bottom: 1px solid #48634c !important; padding-bottom: 20px; }
    .builder-header { align-items: flex-start; }
}
@media (max-width: 520px) {
    .daily-summary { padding: 17px; }
    .meal-card { border-radius: 15px !important; }
    .meal-header { padding: 13px; }
}


/* ============================================================
   TEMA CLARO NUTRILIFE - solo presentación
   Se activa con la clase nl-builder-light, sincronizada con
   el selector de tema existente de la aplicación.
============================================================ */
/* OSCURO: restablece el fondo y tarjetas aunque el tema anterior haya sido claro. */
html.nl-builder-dark body {
    background: radial-gradient(circle at 10% 5%, rgba(104,157,108,.15), transparent 35%),
                linear-gradient(135deg,#0c1d13,#10271a 55%,#0a1a12) !important;
    color: #f1f7f0 !important;
}
html.nl-builder-dark .content-wrapper,
html.nl-builder-dark .content-header { background: transparent !important; }
html.nl-builder-dark .main-header.navbar { background: #102319 !important; }
html.nl-builder-dark .daily-summary,
html.nl-builder-dark .meal-card,
html.nl-builder-dark .new-meal-form {
    background: linear-gradient(145deg,#1a3021,#15291c) !important;
    border-color: rgba(170,209,165,.19) !important;
}
html.nl-builder-dark .macro-progress-card { background: #213a28 !important; border-color: #3b5740 !important; }
html.nl-builder-dark .builder-header h1,
html.nl-builder-dark .meals-heading h2,
html.nl-builder-dark .daily-kcal strong,
html.nl-builder-dark .macro-current strong,
html.nl-builder-dark .meal-title h3,
html.nl-builder-dark .food-info strong { color: #f5faf4 !important; }

html.nl-builder-light {
    --nl-bg: #f5faf5;
    --nl-card: #ffffff;
    --nl-border: #dbe9db;
    --nl-green: #417d4e;
    --nl-green-soft: #567e57;
    --nl-lime: #e6f3c5;
    --nl-text: #1a3524;
    --nl-muted: #667e6a;
}
html.nl-builder-light body,
html.nl-builder-light body .content-wrapper,
html.nl-builder-light body .content-header {
    background: #f5faf5 !important;
    color: #22382a !important;
}
html.nl-builder-light .main-header.navbar { background: #ffffff !important; border-bottom: 1px solid #dbe7da !important; }
html.nl-builder-light .main-header.navbar .nav-link { color: #3f5c46 !important; }
html.nl-builder-light .builder-header h1,
html.nl-builder-light .meals-heading h2,
html.nl-builder-light .daily-kcal strong,
html.nl-builder-light .macro-current strong,
html.nl-builder-light .meal-title h3,
html.nl-builder-light .meal-header-kcal strong,
html.nl-builder-light .food-info strong,
html.nl-builder-light .food-row-values strong,
html.nl-builder-light .meal-footer-values strong,
html.nl-builder-light .food-preview-name,
html.nl-builder-light .food-preview-kcal,
html.nl-builder-light .food-preview-macros strong { color: #203d2a !important; }
html.nl-builder-light .builder-breadcrumb,
html.nl-builder-light .builder-eyebrow,
html.nl-builder-light .section-eyebrow,
html.nl-builder-light .builder-header p,
html.nl-builder-light .meals-heading p,
html.nl-builder-light .summary-label,
html.nl-builder-light .macro-card-top > span,
html.nl-builder-light .meal-title span,
html.nl-builder-light .daily-kcal span,
html.nl-builder-light .daily-remaining,
html.nl-builder-light .macro-current small,
html.nl-builder-light .macro-percent,
html.nl-builder-light .food-info span,
html.nl-builder-light .food-row-values span,
html.nl-builder-light .meal-footer span,
html.nl-builder-light .meal-footer > div > strong,
html.nl-builder-light .meal-footer-values span,
html.nl-builder-light .meal-header-kcal span,
html.nl-builder-light .no-meals p,
html.nl-builder-light .meal-empty span,
html.nl-builder-light .custom-food-divider span { color: #637b68 !important; }
html.nl-builder-light .daily-summary,
html.nl-builder-light .meal-card,
html.nl-builder-light .new-meal-form { background: #ffffff !important; border: 1px solid #dae8da !important; box-shadow: 0 9px 24px rgba(44,91,53,.07) !important; }
html.nl-builder-light .macro-progress-card,
html.nl-builder-light .food-preview,
html.nl-builder-light .food-preview-macros > div { background: #f5faf5 !important; border: 1px solid #e0ebe0 !important; }
html.nl-builder-light .meal-header,
html.nl-builder-light .meal-footer { background: #f1f8f1 !important; border-color: #deebdd !important; }
html.nl-builder-light .daily-main { border-color: #e0ebe0 !important; }
html.nl-builder-light .progress-track { background: #e1ebe1 !important; }
html.nl-builder-light .daily-percentage,
html.nl-builder-light .plan-status.active { background: #e0f3e2 !important; color: #276b3a !important; border-color: #b8dec0 !important; }
html.nl-builder-light .plan-status { background: #f1f4f0 !important; color: #647367 !important; border-color: #dee6de !important; }
html.nl-builder-light .daily-percentage.over { background: #fff0e8 !important; color: #b45941 !important; }
html.nl-builder-light .meal-icon,
html.nl-builder-light .food-row-icon,
html.nl-builder-light .meal-empty-icon,
html.nl-builder-light .no-meals-icon { background: #e4f3e3 !important; color: #397b47 !important; }
html.nl-builder-light .macro-symbol.protein { background: #dcf1df !important; color: #367c4b !important; }
html.nl-builder-light .macro-symbol.carbs { background: #f3f3da !important; color: #85802b !important; }
html.nl-builder-light .macro-symbol.fats { background: #e0f1f5 !important; color: #347d91 !important; }
html.nl-builder-light .secondary-button,
html.nl-builder-light .open-food-button,
html.nl-builder-light .measure-option,
html.nl-builder-light .quantity-control > button,
html.nl-builder-light .custom-form-header > button { background: #e7f4e7 !important; border: 1px solid #bedcc1 !important; color: #316b3d !important; }
html.nl-builder-light .secondary-button:hover,
html.nl-builder-light .open-food-button:hover,
html.nl-builder-light .measure-option:hover,
html.nl-builder-light .quantity-control > button:hover,
html.nl-builder-light .custom-form-header > button:hover { background: #d3ebd5 !important; color: #234e2d !important; }
html.nl-builder-light .measure-option.active { background: #458457 !important; border-color: #458457 !important; color: #fff !important; }
html.nl-builder-light .meal-form-field label,
html.nl-builder-light .drawer-field label,
html.nl-builder-light .custom-macro-grid label { color: #344e39 !important; }
html.nl-builder-light .meal-form-field input,
html.nl-builder-light .food-search,
html.nl-builder-light .food-select,
html.nl-builder-light .custom-input,
html.nl-builder-light .quantity-input-wrapper,
html.nl-builder-light .custom-unit-row { background: #fff !important; color: #203a28 !important; border: 1px solid #ccdfce !important; }
html.nl-builder-light .quantity-input-wrapper input { color: #203a28 !important; }
html.nl-builder-light .meal-form-field input::placeholder,
html.nl-builder-light .food-search::placeholder,
html.nl-builder-light .custom-input::placeholder { color: #87998a !important; }
html.nl-builder-light .food-select option { background: #fff !important; color: #203a28 !important; }
html.nl-builder-light .food-drawer { background: #fff !important; color: #263e2b !important; border-left: 1px solid #d5e5d6 !important; }
html.nl-builder-light .drawer-header { background: #f1f8f1 !important; border-color: #dde9dc !important; }
html.nl-builder-light .drawer-header h3,
html.nl-builder-light .custom-form-header h4 { color: #203a28 !important; }
html.nl-builder-light .drawer-header span,
html.nl-builder-light .custom-form-header span,
html.nl-builder-light .portion-equivalence,
html.nl-builder-light .quantity-input-wrapper span,
html.nl-builder-light .custom-unit-row span { color: #58715e !important; }
html.nl-builder-light .custom-food-form { background: #f7faf7 !important; border-color: #d6e5d6 !important; }
html.nl-builder-light .custom-warning { background: #fff9e8 !important; color: #735b2b !important; border-color: #efe1b8 !important; }
html.nl-builder-light .custom-food-divider { border-color: #d6e7d6 !important; }
html.nl-builder-light .custom-food-divider button { color: #327c46 !important; }
html.nl-builder-light .builder-alert.success { background: #e5f5e8 !important; color: #2d6a3b !important; border-color: #bddfc5 !important; }
html.nl-builder-light .builder-alert.error { background: #fff0ec !important; color: #a74236 !important; border-color: #efc4bd !important; }
html.nl-builder-light .delete-meal-button,
html.nl-builder-light .delete-food-button { background: #fff0f0 !important; color: #b94f4b !important; border-color: #f1cecd !important; }
html.nl-builder-light .no-meals,
html.nl-builder-light .meal-empty { color: #2d4833 !important; }

</style>

@stop



{{-- =========================================================
     JS
========================================================= --}}

@section('js')

<script>

/* Lee el tema global evitando que nuestra propia clase se use como señal. */
(function () {
    const root = document.documentElement;
    const body = document.body;
    const ownClasses = new Set(['nl-builder-light', 'nl-builder-dark']);
    const keys = ['nutrilife-theme', 'nutrilife_theme', 'theme', 'color-theme', 'adminlte-theme', 'themeMode', 'theme-mode', 'darkMode'];

    function fromString(value) {
        const text = String(value || '').toLowerCase().trim();
        if (!text) return null;
        if (text === 'true' || text === '1') return 'dark';
        if (text === 'false' || text === '0') return 'light';
        if (/(^|[^a-z])(dark|oscuro|night)([^a-z]|$)/.test(text)) return 'dark';
        if (/(^|[^a-z])(light|claro|day)([^a-z]|$)/.test(text)) return 'light';
        return null;
    }

    function getMode() {
        // Los atributos explícitos del tema tienen prioridad sobre clases antiguas.
        const elems = [root, body, document.querySelector('.wrapper'), document.querySelector('.content-wrapper')].filter(Boolean);
        for (const el of elems) {
            for (const attr of ['data-theme', 'data-bs-theme', 'data-color-mode', 'data-mode', 'data-theme-mode']) {
                const mode = fromString(el.getAttribute(attr));
                if (mode) return mode;
            }
        }
        for (const el of elems) {
            for (const cls of el.classList) {
                if (ownClasses.has(cls)) continue;
                const mode = fromString(cls);
                if (mode) return mode;
            }
        }
        for (const key of keys) {
            try {
                const mode = fromString(localStorage.getItem(key));
                if (mode) return mode;
            } catch (_) {}
        }
        // Sin indicación explícita, respetar preferencia del navegador.
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
    }

    function syncTheme() {
        const mode = getMode();
        root.classList.toggle('nl-builder-light', mode === 'light');
        root.classList.toggle('nl-builder-dark', mode === 'dark');
    }
    syncTheme();
    const observer = new MutationObserver(syncTheme);
    [root, body, document.querySelector('.wrapper'), document.querySelector('.content-wrapper')]
        .filter(Boolean).forEach(el => observer.observe(el, {
            attributes: true,
            attributeFilter: ['class','data-theme','data-bs-theme','data-color-mode','data-mode','data-theme-mode']
        }));
    window.addEventListener('storage', syncTheme);
    document.addEventListener('click', () => {
        requestAnimationFrame(syncTheme);
        setTimeout(syncTheme, 100);
    });
    if (window.matchMedia) {
        const preference = window.matchMedia('(prefers-color-scheme: dark)');
        if (preference.addEventListener) preference.addEventListener('change', syncTheme);
    }
})();


document.addEventListener('DOMContentLoaded', function () {

    console.log('NutriLife Diet Builder JS cargado correctamente');

    /* =========================================================
       BUSCADOR DE ALIMENTOS
    ========================================================= */

    document.querySelectorAll('.food-search').forEach(function (search) {

        search.addEventListener('input', function () {

            const select = document.getElementById(
                this.dataset.target
            );

            if (!select) {
                return;
            }

            const text = this.value
                .toLowerCase()
                .trim();

            Array.from(select.options).forEach(function (option) {

                if (!option.value) {
                    option.hidden = false;
                    return;
                }

                const name = (
                    option.dataset.name || ''
                ).toLowerCase();

                option.hidden = !name.includes(text);

            });

        });

    });


    /* =========================================================
       CAMBIO DE ALIMENTO
    ========================================================= */

    document.querySelectorAll('.food-select').forEach(function (select) {

        select.addEventListener('change', function () {

            const mealId = this.id.replace(
                'foodSelect',
                ''
            );

            const modeInput = document.getElementById(
                'measureMode' + mealId
            );

            const quantityInput = document.getElementById(
                'quantity' + mealId
            );

            const portionButton = document.getElementById(
                'measurePortion' + mealId
            );

            const gramsButton = document.getElementById(
                'measureGrams' + mealId
            );

            if (modeInput) {
                modeInput.value = 'portion';
            }

            if (quantityInput) {
                quantityInput.value = 1;
                quantityInput.min = 0.5;
                quantityInput.step = 0.5;
            }

            if (portionButton) {
                portionButton.classList.add('active');
            }

            if (gramsButton) {
                gramsButton.classList.remove('active');
            }

            updateFoodPreview(mealId);

        });

    });


    /* =========================================================
       ESC CIERRA DRAWERS
    ========================================================= */

    document.addEventListener('keydown', function (event) {

        if (event.key !== 'Escape') {
            return;
        }

        document
            .querySelectorAll('.food-drawer-overlay.active')
            .forEach(function (overlay) {

                overlay.classList.remove('active');

            });

        document.body.style.overflow = '';

    });

});


/* =============================================================
   AGREGAR COMIDA
============================================================= */

function toggleMealForm() {

    const form = document.getElementById('mealForm');

    if (!form) {

        console.error(
            'NutriLife: no existe #mealForm'
        );

        return;
    }

    form.classList.toggle('active');

}


/* =============================================================
   DRAWER DE ALIMENTOS
============================================================= */

function openFoodDrawer(mealId) {

    const overlay = document.getElementById(
        'drawerOverlay' + mealId
    );

    if (!overlay) {

        console.error(
            'NutriLife: no existe drawerOverlay' + mealId
        );

        return;
    }

    overlay.classList.add('active');

    document.body.style.overflow = 'hidden';

    updateFoodPreview(mealId);

}


function forceCloseFoodDrawer(mealId) {

    const overlay = document.getElementById(
        'drawerOverlay' + mealId
    );

    if (!overlay) {
        return;
    }

    overlay.classList.remove('active');

    document.body.style.overflow = '';

}


function closeFoodDrawer(mealId, event) {

    const overlay = document.getElementById(
        'drawerOverlay' + mealId
    );

    if (!overlay) {
        return;
    }

    if (event.target === overlay) {

        forceCloseFoodDrawer(mealId);

    }

}


/* =============================================================
   MODO PORCIONES / GRAMOS
============================================================= */

function setMeasureMode(mealId, mode) {

    const modeInput = document.getElementById(
        'measureMode' + mealId
    );

    const quantityInput = document.getElementById(
        'quantity' + mealId
    );

    const portionButton = document.getElementById(
        'measurePortion' + mealId
    );

    const gramsButton = document.getElementById(
        'measureGrams' + mealId
    );

    const select = document.getElementById(
        'foodSelect' + mealId
    );


    if (
        !modeInput ||
        !quantityInput ||
        !portionButton ||
        !gramsButton
    ) {

        console.error(
            'NutriLife: controles incompletos para comida',
            mealId
        );

        return;
    }


    modeInput.value = mode;


    portionButton.classList.toggle(
        'active',
        mode === 'portion'
    );

    gramsButton.classList.toggle(
        'active',
        mode === 'grams'
    );


    if (mode === 'portion') {

        quantityInput.value = 1;

        quantityInput.min = 0.5;

        quantityInput.step = 0.5;

    } else {

        let grams = 100;

        if (
            select &&
            select.selectedIndex >= 0
        ) {

            const option =
                select.options[
                    select.selectedIndex
                ];

            if (
                option &&
                option.value &&
                option.dataset.portion
            ) {

                grams =
                    parseFloat(
                        option.dataset.portion
                    ) || 100;

            }

        }

        quantityInput.value = grams;

        quantityInput.min = 1;

        quantityInput.step = 1;

    }


    updateFoodPreview(mealId);

}


/* =============================================================
   BOTONES + Y -
============================================================= */

function changeQuantity(mealId, direction) {

    const quantityInput = document.getElementById(
        'quantity' + mealId
    );

    const modeInput = document.getElementById(
        'measureMode' + mealId
    );


    if (!quantityInput || !modeInput) {
        return;
    }


    let value =
        parseFloat(quantityInput.value) || 0;


    const step =
        modeInput.value === 'portion'
            ? 0.5
            : 10;


    value += direction * step;


    if (modeInput.value === 'portion') {

        value = Math.max(
            0.5,
            value
        );

    } else {

        value = Math.max(
            1,
            value
        );

    }


    quantityInput.value = value;


    updateFoodPreview(mealId);

}


/* =============================================================
   PREVIEW NUTRICIONAL
============================================================= */

function updateFoodPreview(mealId) {

    const select = document.getElementById(
        'foodSelect' + mealId
    );

    const quantityInput = document.getElementById(
        'quantity' + mealId
    );

    const gramsInput = document.getElementById(
        'grams' + mealId
    );

    const modeInput = document.getElementById(
        'measureMode' + mealId
    );

    const quantityUnit = document.getElementById(
        'quantityUnit' + mealId
    );

    const equivalence = document.getElementById(
        'portionEquivalence' + mealId
    );

    const preview = document.getElementById(
        'foodPreview' + mealId
    );


    if (
        !select ||
        !quantityInput ||
        !gramsInput ||
        !modeInput ||
        !preview
    ) {

        return;

    }


    const option =
        select.options[
            select.selectedIndex
        ];


    if (!option || !option.value) {

        preview.innerHTML =
            '<div class="preview-placeholder">' +
                '<i class="fas fa-apple-whole"></i>' +
                '<span>' +
                    'Selecciona un alimento para ver sus valores nutricionales.' +
                '</span>' +
            '</div>';


        if (equivalence) {

            equivalence.textContent =
                'Selecciona un alimento.';

        }

        return;

    }


    const portionGrams =
        parseFloat(
            option.dataset.portion
        ) || 100;


    const portionDescription =
        option.dataset.portionDescription ||
        '1 porción';


    const quantity =
        parseFloat(
            quantityInput.value
        ) || 0;


    const mode =
        modeInput.value;


    let grams = 0;

    let visibleDescription = '';


    /* =========================================================
       PORCIONES
    ========================================================= */

    if (mode === 'portion') {

        grams =
            quantity * portionGrams;


        if (quantityUnit) {

            const description =
                portionDescription
                    .toLowerCase();


            if (description.includes('pieza')) {

                quantityUnit.textContent =
                    quantity === 1
                        ? 'pieza'
                        : 'piezas';

            } else {

                quantityUnit.textContent =
                    quantity === 1
                        ? 'porción'
                        : 'porciones';

            }

        }


        visibleDescription =
            quantity +
            ' × ' +
            portionDescription;


        if (equivalence) {

            equivalence.innerHTML =
                '<span>' +
                    visibleDescription +
                '</span>' +

                '<strong>' +
                    '≈ ' +
                    grams.toFixed(0) +
                    ' g' +
                '</strong>';

        }

    }


    /* =========================================================
       GRAMOS
    ========================================================= */

    else {

        grams = quantity;


        if (quantityUnit) {

            quantityUnit.textContent =
                'g';

        }


        const equivalentPortions =
            portionGrams > 0
                ? grams / portionGrams
                : 0;


        visibleDescription =
            grams.toFixed(0) + ' g';


        if (equivalence) {

            equivalence.innerHTML =
                '<span>' +
                    grams.toFixed(0) +
                    ' g' +
                '</span>' +

                '<strong>' +
                    '≈ ' +
                    equivalentPortions.toFixed(2) +
                    ' porciones' +
                '</strong>';

        }

    }


    /* =========================================================
       INPUT QUE RECIBE LARAVEL
    ========================================================= */

    gramsInput.value =
        grams.toFixed(2);


    /* =========================================================
       MACROS
    ========================================================= */

    const factor =
        portionGrams > 0
            ? grams / portionGrams
            : 0;


    const kcal =
        (
            parseFloat(
                option.dataset.kcal
            ) || 0
        ) * factor;


    const protein =
        (
            parseFloat(
                option.dataset.protein
            ) || 0
        ) * factor;


    const carbs =
        (
            parseFloat(
                option.dataset.carbs
            ) || 0
        ) * factor;


    const fat =
        (
            parseFloat(
                option.dataset.fat
            ) || 0
        ) * factor;


    /* =========================================================
       MOSTRAR PREVIEW
    ========================================================= */

    preview.innerHTML =
        '<div class="food-preview-name">' +
            option.text.trim() +
        '</div>' +

        '<div class="food-preview-portion">' +
            visibleDescription +
            ' · ' +
            grams.toFixed(0) +
            ' g' +
        '</div>' +

        '<div class="food-preview-kcal">' +
            kcal.toFixed(0) +
            ' <small>kcal</small>' +
        '</div>' +

        '<div class="food-preview-macros">' +

            '<div>' +
                '<span>PROTEÍNA</span>' +
                '<strong>' +
                    protein.toFixed(1) +
                    'g' +
                '</strong>' +
            '</div>' +

            '<div>' +
                '<span>CARBOHIDRATOS</span>' +
                '<strong>' +
                    carbs.toFixed(1) +
                    'g' +
                '</strong>' +
            '</div>' +

            '<div>' +
                '<span>GRASAS</span>' +
                '<strong>' +
                    fat.toFixed(1) +
                    'g' +
                '</strong>' +
            '</div>' +

        '</div>';

}
/* =============================================================
   ALIMENTO PERSONALIZADO
============================================================= */

function showCustomFoodForm(mealId) {

    const customForm =
        document.getElementById(
            'customFoodForm' + mealId
        );

    if (!customForm) {
        return;
    }


    /*
     * Ocultamos el formulario normal del catálogo
     * dentro del mismo drawer.
     */

    const drawer =
        customForm.closest(
            '.food-drawer'
        );


    if (drawer) {

        const catalogForm =
            drawer.querySelector(
                '.drawer-form'
            );

        const divider =
            drawer.querySelector(
                '.custom-food-divider'
            );


        if (catalogForm) {

            catalogForm.style.display =
                'none';

        }


        if (divider) {

            divider.style.display =
                'none';

        }

    }


    customForm.classList.add(
        'active'
    );

}



function hideCustomFoodForm(mealId) {

    const customForm =
        document.getElementById(
            'customFoodForm' + mealId
        );

    if (!customForm) {
        return;
    }


    const drawer =
        customForm.closest(
            '.food-drawer'
        );


    customForm.classList.remove(
        'active'
    );


    if (drawer) {

        const catalogForm =
            drawer.querySelector(
                '.drawer-form'
            );

        const divider =
            drawer.querySelector(
                '.custom-food-divider'
            );


        if (catalogForm) {

            catalogForm.style.display =
                '';

        }


        if (divider) {

            divider.style.display =
                '';

        }

    }

}
</script>

@stop
