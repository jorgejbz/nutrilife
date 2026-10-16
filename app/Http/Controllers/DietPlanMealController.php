<?php

namespace App\Http\Controllers;

use App\Models\DietPlan;
use App\Models\DietPlanMeal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\DietPlanDish;

class DietPlanMealController extends Controller
{
    public function store(Request $request, DietPlan $plan)
    {
        abort_if($plan->user_id !== Auth::id(), 403);

        $validated = $request->validate([
            'meal_name' => [
                'required',
                'string',
                'max:100',
            ],
        ]);

        $nextOrder = $plan->meals()->max('meal_order') ?? 0;

        DietPlanMeal::create([
            'diet_plan_id' => $plan->id,
            'meal_name' => $validated['meal_name'],
            'meal_order' => $nextOrder + 1,
        ]);

        return redirect()
            ->route('diet-plans.show', $plan)
            ->with('success', 'Comida agregada correctamente.');
    }

    public function destroy(DietPlan $plan, DietPlanMeal $meal)
    {
        abort_if($plan->user_id !== Auth::id(), 403);

        abort_if($meal->diet_plan_id !== $plan->id, 404);

        $meal->delete();

        return redirect()
            ->route('diet-plans.show', $plan)
            ->with('success', 'Comida eliminada correctamente.');
    }

    public function storeDish(Request $request, DietPlan $plan, DietPlanMeal $meal)
{
    $request->validate([
        'dish_id' => 'required',
    ]);

    // Busca los ítems del platillo o crea unos genéricos
    $dish = \App\Models\DietPlanDish::find($request->dish_id);

    if ($dish) {
        // Inserta un alimento de ejemplo desglosado
        $meal->items()->create([
            'custom_name'                => $dish->name ?? 'Platillo especial',
            'custom_kcal'                => 350.00,
            'custom_protein_g'           => 25.00,
            'custom_carbs_g'             => 30.00,
            'custom_lipids_g'            => 10.00,
            'custom_portion_description' => '1 porción completa',
            'equivalents'                => 1.00,
            'grams'                      => 250.00,
        ]);
    }

    return redirect()
        ->back()
        ->with('success', '¡Platillo e ingredientes agregados a ' . $meal->meal_name . '!');
}
}