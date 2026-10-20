<?php

namespace App\Http\Controllers;

use App\Models\DietPlan;
use App\Models\DietPlanItem;
use App\Models\DietPlanMeal;
use App\Models\Food;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Alimentos de una comida (tabla diet_plan_items).
 *
 * Todos salen del catálogo `foods` (food_id NOT NULL en la BD definitiva).
 * Se guardan los gramos y sus equivalentes; kcal y macros se calculan del
 * catálogo. Rutas con scopeBindings: {item} debe ser de {meal} y {meal}
 * de {plan}; el permiso es el del plan (DietPlanPolicy@update).
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

    public function update(Request $request, DietPlan $plan, DietPlanMeal $meal, DietPlanItem $item)
    {
        Gate::authorize('update', $plan);

        $validated = $request->validate([
            'grams' => $this->gramsRules(),
        ], $this->messages());

        $food = $item->food;
        $grams = (float) $validated['grams'];

        $item->update([
            'grams' => $grams,
            'equivalents' => $food ? $this->equivalents($food, $grams) : $item->equivalents,
        ]);

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

    /**
     * Equivalentes = gramos / porción del catálogo (DECIMAL(4,2), máx. 99.99).
     */
    private function equivalents(Food $food, float $grams): float
    {
        $equivalents = DietPlanItem::equivalentsFor($food, $grams);

        if ($equivalents === null) {
            throw ValidationException::withMessages([
                'food_id' => 'El alimento seleccionado no tiene una porción válida en el catálogo.',
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
        ];
    }
}
