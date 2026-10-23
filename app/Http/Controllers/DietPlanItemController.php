<?php

namespace App\Http\Controllers;

use App\Models\DietPlan;
use App\Models\DietPlanDish;
use App\Models\DietPlanDishItem;
use App\Models\DietPlanItem;
use App\Models\DietPlanMeal;
use App\Models\Food;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Alimentos de una comida (tabla diet_plan_items).
 *
 * Tres formas de agregarlos:
 *  - store:       del catálogo `foods`; se guardan gramos y equivalentes, y
 *                 kcal y macros se calculan del catálogo.
 *  - storeCustom: personalizado (food_id NULL); se guarda su aporte total.
 *  - storeDish:   un platillo; sus ingredientes se COPIAN como renglones.
 *
 * store y storeCustom aceptan diet_plan_dish_id para agregar un ingrediente
 * a un platillo que ya está en la comida. Esas copias se personalizan para
 * el paciente (substitute, update, scaleDish) sin tocar diet_plan_dish_items:
 * el platillo del catálogo y los demás planes no cambian.
 *
 * Rutas con scopeBindings: {item} debe ser de {meal} y {meal} de {plan}; el
 * permiso es el del plan (DietPlanPolicy@update).
 */
class DietPlanItemController extends Controller
{
    public function store(Request $request, DietPlan $plan, DietPlanMeal $meal)
    {
        Gate::authorize('update', $plan);

        $validated = $request->validate([
            'food_id' => [
                'required',
                'integer',
                Rule::exists('foods', 'id')->where('active', true),
            ],
            'grams' => $this->gramsRules(),
            'diet_plan_dish_id' => $this->dishGroupRules($meal),
        ], $this->messages());

        $food = Food::findOrFail($validated['food_id']);
        $grams = (float) $validated['grams'];
        $dishId = $validated['diet_plan_dish_id'] ?? null;

        $meal->items()->create([
            'food_id' => $food->id,
            'grams' => $grams,
            'equivalents' => $this->equivalents($food, $grams),
            ...$this->dishGroupColumns($meal, $dishId),
        ]);

        return $this->backToMeal($plan, $meal, $dishId)
            ->with('success', $food->name.' se agregó a '.$this->targetLabel($meal, $dishId).'.');
    }

    /**
     * Alimento personalizado: no está en el catálogo (food_id NULL). kcal y
     * macros son el aporte de la cantidad indicada; cuenta como 1 porción.
     * No tiene avisos de alergias ni patologías (no hay datos del catálogo).
     */
    public function storeCustom(Request $request, DietPlan $plan, DietPlanMeal $meal)
    {
        Gate::authorize('update', $plan);

        $validated = $request->validate([
            'custom_name' => ['required', 'string', 'max:255'],
            'custom_portion_description' => ['nullable', 'string', 'max:255'],
            'grams' => $this->gramsRules(),
            'custom_kcal' => $this->nutrientRules(DietPlan::MAX_KCAL),
            'custom_protein_g' => $this->nutrientRules(DietPlan::MAX_MACRO_G),
            'custom_carbs_g' => $this->nutrientRules(DietPlan::MAX_MACRO_G),
            'custom_lipids_g' => $this->nutrientRules(DietPlan::MAX_MACRO_G),
            'diet_plan_dish_id' => $this->dishGroupRules($meal),
        ], $this->messages());

        $dishId = $validated['diet_plan_dish_id'] ?? null;
        unset($validated['diet_plan_dish_id']);

        $meal->items()->create([
            ...$validated,
            'custom_portion_description' => $validated['custom_portion_description'] ?? null,
            'equivalents' => 1,
            ...$this->dishGroupColumns($meal, $dishId),
        ]);

        return $this->backToMeal($plan, $meal, $dishId)
            ->with('success', $validated['custom_name'].' se agregó a '.$this->targetLabel($meal, $dishId).'.');
    }

    /**
     * Agrega un platillo del sistema o propio: COPIA sus ingredientes como
     * renglones de la comida (diet_plan_dish_id = platillo de origen), así
     * que editar o borrar el platillo no cambia el plan. No se inventan
     * valores: un ingrediente sin datos se copia sin datos y un platillo sin
     * ingredientes no se puede agregar.
     */
    public function storeDish(Request $request, DietPlan $plan, DietPlanMeal $meal)
    {
        Gate::authorize('update', $plan);

        $validated = $request->validate([
            'diet_plan_dish_id' => ['required', 'integer'],
        ], $this->messages());

        // Un platillo de otra cuenta se trata como inexistente
        $dish = DietPlanDish::visibleTo(Auth::id())
            ->with('items.food')
            ->find($validated['diet_plan_dish_id']);

        if (! $dish) {
            throw ValidationException::withMessages([
                'diet_plan_dish_id' => 'El platillo seleccionado no está disponible.',
            ]);
        }

        if ($dish->items->isEmpty()) {
            throw ValidationException::withMessages([
                'diet_plan_dish_id' => '«'.$dish->name.'» no tiene ingredientes registrados, así que no aporta datos nutricionales.',
            ]);
        }

        $rows = $dish->items->map(function (DietPlanDishItem $ingredient) use ($dish) {
            $row = [
                'diet_plan_dish_id' => $dish->id,
                'grams' => (float) $ingredient->grams,
                'custom_image_url' => $dish->image_url,
            ];

            if ($ingredient->isCustom()) {
                return [
                    ...$row,
                    ...$ingredient->only(['custom_name', ...array_values(DietPlanItem::CUSTOM_NUTRIENTS)]),
                    'equivalents' => 1,
                ];
            }

            return [
                ...$row,
                'food_id' => $ingredient->food_id,
                'equivalents' => $this->equivalents($ingredient->food, (float) $ingredient->grams),
            ];
        });

        DB::transaction(fn () => $meal->items()->createMany($rows->all()));

        return redirect()
            ->to(route('diet-plans.show', $plan).'#comida-'.$meal->id)
            ->with('success', '«'.$dish->name.'» se agregó a '.$meal->meal_name
                .' ('.$rows->count().' '.($rows->count() === 1 ? 'ingrediente' : 'ingredientes').').');
    }

    public function update(Request $request, DietPlan $plan, DietPlanMeal $meal, DietPlanItem $item)
    {
        Gate::authorize('update', $plan);

        $validated = $request->validate([
            'grams' => $this->gramsRules(),
            // Del catálogo se puede capturar en porciones (foods.portion_grams)
            'unit' => ['nullable', Rule::in(['g', 'porciones'])],
        ], $this->messages());

        $grams = (float) $validated['grams'];

        if (($validated['unit'] ?? 'g') === 'porciones' && ! $item->isCustom()) {
            $grams = $this->gramsFromPortions($item, $grams);
        }

        if ($item->isCustom()) {
            $item->update([
                'grams' => $grams,
                ...$this->scaledCustomNutrients($item, $grams),
            ]);
        } else {
            $food = $item->food;

            $item->update([
                'grams' => $grams,
                'equivalents' => $food ? $this->equivalents($food, $grams) : $item->equivalents,
            ]);
        }

        return $this->backToMeal($plan, $meal, $item->diet_plan_dish_id)
            ->with('success', 'Cantidad de '.$item->displayName().' actualizada.');
    }

    /**
     * Sustituye un renglón por otro alimento (del catálogo o personalizado)
     * conservando su lugar y su platillo de origen. Solo cambia la copia de
     * este plan; la receta (diet_plan_dish_items) queda igual.
     */
    public function substitute(Request $request, DietPlan $plan, DietPlanMeal $meal, DietPlanItem $item)
    {
        Gate::authorize('update', $plan);

        $previous = $item->displayName();

        if ($request->filled('custom_name')) {
            $validated = $request->validate([
                'custom_name' => ['required', 'string', 'max:255'],
                'custom_portion_description' => ['nullable', 'string', 'max:255'],
                'grams' => $this->gramsRules(),
                'custom_kcal' => $this->nutrientRules(DietPlan::MAX_KCAL),
                'custom_protein_g' => $this->nutrientRules(DietPlan::MAX_MACRO_G),
                'custom_carbs_g' => $this->nutrientRules(DietPlan::MAX_MACRO_G),
                'custom_lipids_g' => $this->nutrientRules(DietPlan::MAX_MACRO_G),
            ], $this->messages());

            $item->update([
                ...$validated,
                'custom_portion_description' => $validated['custom_portion_description'] ?? null,
                'food_id' => null,
                'equivalents' => 1,
            ]);
        } else {
            $validated = $request->validate([
                'food_id' => [
                    'required',
                    'integer',
                    Rule::exists('foods', 'id')->where('active', true),
                ],
                'grams' => $this->gramsRules(),
            ], $this->messages());

            $food = Food::findOrFail($validated['food_id']);
            $grams = (float) $validated['grams'];

            // Un renglón del catálogo no lleva custom_* (CHECK *_food_or_custom_check)
            $item->update([
                'food_id' => $food->id,
                'grams' => $grams,
                'equivalents' => $this->equivalents($food, $grams),
                'custom_name' => null,
                'custom_kcal' => null,
                'custom_protein_g' => null,
                'custom_carbs_g' => null,
                'custom_lipids_g' => null,
                'custom_portion_description' => null,
            ]);
        }

        $item->load('food');

        return $this->backToMeal($plan, $meal, $item->diet_plan_dish_id)
            ->with('success', $previous.' se sustituyó por '.$item->displayName().'.');
    }

    /**
     * Ajusta las porciones de un platillo ya agregado: multiplica los gramos
     * de todos sus ingredientes (y el aporte de los personalizados). Todo o
     * nada: si un ingrediente pasaría los límites de la BD no se guarda nada.
     */
    public function scaleDish(Request $request, DietPlan $plan, DietPlanMeal $meal, int $dish)
    {
        Gate::authorize('update', $plan);

        $items = $meal->items()->with('food')->where('diet_plan_dish_id', $dish)->get();

        abort_if($items->isEmpty(), 404);

        $validated = $request->validate([
            'factor' => ['required', 'numeric', 'decimal:0,2', 'min:0.1', 'max:10'],
        ], [
            'factor.required' => 'Indica por cuánto multiplicar las porciones.',
            'factor.numeric' => 'Las porciones deben ser un número.',
            'factor.decimal' => 'Las porciones admiten como máximo 2 decimales.',
            'factor.min' => 'Las porciones deben ser de al menos 0.1.',
            'factor.max' => 'Las porciones no pueden ser más de 10 veces la cantidad actual.',
        ]);

        $factor = (float) $validated['factor'];

        // Primero se calcula todo (lanza error de validación si algo no cabe)
        $changes = $items->map(function (DietPlanItem $item) use ($factor) {
            $grams = round((float) $item->grams * $factor, 2);

            if ($grams < 0.1 || $grams > DietPlanItem::MAX_GRAMS) {
                throw ValidationException::withMessages([
                    'factor' => 'Con ese ajuste '.$item->displayName().' quedaría fuera de 0.1 a '.DietPlanItem::MAX_GRAMS.' g.',
                ]);
            }

            if ($item->isCustom()) {
                return [$item, ['grams' => $grams, ...$this->scaledCustomNutrients($item, $grams)]];
            }

            return [$item, [
                'grams' => $grams,
                'equivalents' => $item->food ? $this->equivalents($item->food, $grams) : $item->equivalents,
            ]];
        });

        DB::transaction(fn () => $changes->each(fn (array $change) => $change[0]->update($change[1])));

        return $this->backToMeal($plan, $meal, $dish)
            ->with('success', 'Porciones de '.$this->targetLabel($meal, $dish).' ajustadas (× '
                .rtrim(rtrim(number_format($factor, 2, '.', ''), '0'), '.').').');
    }

    public function destroy(DietPlan $plan, DietPlanMeal $meal, DietPlanItem $item)
    {
        Gate::authorize('update', $plan);

        $dishId = $item->diet_plan_dish_id;
        $name = $item->displayName();
        $item->delete();

        return $this->backToMeal($plan, $meal, $dishId)
            ->with('success', $name.' se quitó de '.$this->targetLabel($meal, $dishId).'.');
    }

    /* ------------------------------------------------------------------ */

    /**
     * diet_plan_dish_id opcional: solo un platillo que ya tiene ingredientes
     * en ESTA comida (así no se liga un renglón a platillos de otra cuenta).
     */
    private function dishGroupRules(DietPlanMeal $meal): array
    {
        return [
            'nullable',
            'integer',
            Rule::exists('diet_plan_items', 'diet_plan_dish_id')->where('diet_plan_meal_id', $meal->id),
        ];
    }

    /** Columnas que ligan un ingrediente nuevo al platillo (y su foto) dentro de la comida. */
    private function dishGroupColumns(DietPlanMeal $meal, ?int $dishId): array
    {
        if ($dishId === null) {
            return [];
        }

        return [
            'diet_plan_dish_id' => $dishId,
            'custom_image_url' => $meal->items()->where('diet_plan_dish_id', $dishId)->value('custom_image_url'),
        ];
    }

    /** «el platillo X» si el renglón es de un platillo, si no el nombre de la comida. */
    private function targetLabel(DietPlanMeal $meal, ?int $dishId): string
    {
        $dishName = $dishId ? DietPlanDish::whereKey($dishId)->value('name') : null;

        return $dishName ? '«'.$dishName.'» ('.$meal->meal_name.')' : $meal->meal_name;
    }

    /** Regresa al plan, a la tarjeta del platillo si aplica o a la de la comida. */
    private function backToMeal(DietPlan $plan, DietPlanMeal $meal, ?int $dishId = null)
    {
        $anchor = $dishId && $meal->items()->where('diet_plan_dish_id', $dishId)->exists()
            ? 'platillo-'.$meal->id.'-'.$dishId
            : 'comida-'.$meal->id;

        return redirect()->to(route('diet-plans.show', $plan).'#'.$anchor);
    }

    /** Porciones del catálogo → gramos, dentro del límite de diet_plan_items.grams. */
    private function gramsFromPortions(DietPlanItem $item, float $portions): float
    {
        $portion = (float) ($item->food?->portion_grams ?? 0);

        if ($portion <= 0) {
            throw ValidationException::withMessages([
                'grams' => $item->displayName().' no tiene una porción válida en el catálogo; captura la cantidad en gramos.',
            ]);
        }

        $grams = round($portions * $portion, 2);

        if ($grams < 0.1 || $grams > DietPlanItem::MAX_GRAMS) {
            throw ValidationException::withMessages([
                'grams' => 'Esa cantidad de porciones queda fuera de 0.1 a '.DietPlanItem::MAX_GRAMS.' g.',
            ]);
        }

        return $grams;
    }

    /** diet_plan_items.grams DECIMAL(6,2) */
    private function gramsRules(): array
    {
        return ['required', 'numeric', 'decimal:0,2', 'min:0.1', 'max:'.DietPlanItem::MAX_GRAMS];
    }

    /** custom_kcal DECIMAL(7,2) y macros DECIMAL(6,2) */
    private function nutrientRules(float $max): array
    {
        return ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:'.$max];
    }

    /**
     * Equivalentes = gramos / porción del catálogo (DECIMAL(4,2), máx. 99.99).
     */
    private function equivalents(Food $food, float $grams): float
    {
        $equivalents = DietPlanItem::equivalentsFor($food, $grams);

        if ($equivalents === null) {
            throw ValidationException::withMessages([
                'food_id' => 'El alimento '.$food->name.' no tiene una porción válida en el catálogo.',
            ]);
        }

        if ($equivalents > DietPlanItem::MAX_EQUIVALENTS) {
            throw ValidationException::withMessages([
                'grams' => 'La cantidad equivale a más de '.DietPlanItem::MAX_EQUIVALENTS
                    .' porciones de '.$food->name.'. Revisa los gramos.',
            ]);
        }

        return max($equivalents, 0.01);
    }

    /** Reescala el aporte de un personalizado sin pasar los límites de la BD. */
    private function scaledCustomNutrients(DietPlanItem $item, float $grams): array
    {
        $scaled = $item->customNutrientsFor($grams);

        foreach (DietPlanItem::CUSTOM_NUTRIENTS as $key => $column) {
            $max = $key === 'kcal' ? DietPlan::MAX_KCAL : DietPlan::MAX_MACRO_G;

            if ($scaled[$column] !== null && $scaled[$column] > $max) {
                throw ValidationException::withMessages([
                    'grams' => 'Con esa cantidad el aporte de '.$item->custom_name.' supera el máximo permitido. Revisa los gramos.',
                ]);
            }
        }

        return $scaled;
    }

    private function messages(): array
    {
        return [
            'food_id.required' => 'Selecciona un alimento del catálogo.',
            'food_id.integer' => 'Selecciona un alimento del catálogo.',
            'food_id.exists' => 'El alimento seleccionado no está disponible.',
            'grams.required' => 'Indica la cantidad en gramos.',
            'grams.numeric' => 'La cantidad debe ser un número.',
            'grams.decimal' => 'La cantidad admite como máximo 2 decimales.',
            'grams.min' => 'La cantidad debe ser de al menos 0.1 g.',
            'grams.max' => 'La cantidad no puede ser mayor a '.DietPlanItem::MAX_GRAMS.' g.',
            'custom_name.required' => 'Escribe el nombre del alimento.',
            'custom_name.max' => 'El nombre admite como máximo 255 caracteres.',
            'custom_portion_description.max' => 'La porción admite como máximo 255 caracteres.',
            'custom_kcal.required' => 'Indica las kilocalorías.',
            'custom_protein_g.required' => 'Indica las proteínas (0 si no tiene).',
            'custom_carbs_g.required' => 'Indica los carbohidratos (0 si no tiene).',
            'custom_lipids_g.required' => 'Indica las grasas (0 si no tiene).',
            'custom_kcal.max' => 'Las kilocalorías no pueden ser mayores a '.DietPlan::MAX_KCAL.'.',
            'numeric' => 'Las kilocalorías y los macros deben ser números.',
            'decimal' => 'Las kilocalorías y los macros admiten como máximo 2 decimales.',
            'min' => 'Las kilocalorías y los macros no pueden ser negativos.',
            'max' => 'Los macros no pueden ser mayores a '.DietPlan::MAX_MACRO_G.' g.',
            'diet_plan_dish_id.required' => 'Selecciona un platillo.',
            'diet_plan_dish_id.integer' => 'Selecciona un platillo.',
            'diet_plan_dish_id.exists' => 'El platillo ya no está en esta comida.',
            'unit.in' => 'Selecciona gramos o porciones.',
        ];
    }
}
