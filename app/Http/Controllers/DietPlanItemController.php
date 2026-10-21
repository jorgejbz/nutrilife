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
        ], $this->messages());

        $food = Food::findOrFail($validated['food_id']);
        $grams = (float) $validated['grams'];

        $meal->items()->create([
            'food_id' => $food->id,
            'grams' => $grams,
            'equivalents' => $this->equivalents($food, $grams),
        ]);

        return redirect()
            ->to(route('diet-plans.show', $plan).'#comida-'.$meal->id)
            ->with('success', $food->name.' se agregó a '.$meal->meal_name.'.');
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
        ], $this->messages());

        $meal->items()->create([
            ...$validated,
            'custom_portion_description' => $validated['custom_portion_description'] ?? null,
            'equivalents' => 1,
        ]);

        return redirect()
            ->to(route('diet-plans.show', $plan).'#comida-'.$meal->id)
            ->with('success', $validated['custom_name'].' se agregó a '.$meal->meal_name.'.');
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
        ], $this->messages());

        $grams = (float) $validated['grams'];

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

        return redirect()
            ->to(route('diet-plans.show', $plan).'#comida-'.$meal->id)
            ->with('success', 'Cantidad actualizada.');
    }

    public function destroy(DietPlan $plan, DietPlanMeal $meal, DietPlanItem $item)
    {
        Gate::authorize('update', $plan);

        $item->delete();

        return redirect()
            ->to(route('diet-plans.show', $plan).'#comida-'.$meal->id)
            ->with('success', 'Alimento eliminado de '.$meal->meal_name.'.');
    }

    /* ------------------------------------------------------------------ */

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
        ];
    }
}
