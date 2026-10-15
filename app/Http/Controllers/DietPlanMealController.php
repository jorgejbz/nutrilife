<?php

namespace App\Http\Controllers;

use App\Models\DietPlan;
use App\Models\DietPlanMeal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}