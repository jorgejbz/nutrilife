<?php

namespace App\Http\Controllers;

use App\Models\DietPlan;
use App\Models\DietPlanMeal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Tiempos de comida de un plan (tabla diet_plan_meals).
 *
 * Las rutas usan scopeBindings: {meal} se busca dentro de {plan}->meals(),
 * así que una comida de otro plan responde 404. El permiso es el del plan
 * (DietPlanPolicy@update).
 */
class DietPlanMealController extends Controller
{
    public function store(Request $request, DietPlan $plan)
    {
        Gate::authorize('update', $plan);

        $validated = $request->validate($this->rules(), $this->messages());

        $nextOrder = (int) $plan->meals()->max('meal_order') + 1;

        // meal_order es TINYINT UNSIGNED
        if ($nextOrder > DietPlanMeal::MAX_ORDER) {
            return back()->withErrors([
                'meal_name' => 'El plan alcanzó el máximo de tiempos de comida.',
            ]);
        }

        $meal = $plan->meals()->create([
            'meal_name' => trim($validated['meal_name']),
            'meal_order' => $nextOrder,
            'meal_time' => $validated['meal_time'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->to(route('diet-plans.show', $plan).'#comida-'.$meal->id)
            ->with('success', '"'.$meal->meal_name.'" se agregó al plan.');
    }

    /** Cambiar nombre, horario o indicación de un tiempo de comida. */
    public function update(Request $request, DietPlan $plan, DietPlanMeal $meal)
    {
        Gate::authorize('update', $plan);

        $validated = $request->validate($this->rules(), $this->messages());

        $meal->update([
            'meal_name' => trim($validated['meal_name']),
            'meal_time' => $validated['meal_time'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->to(route('diet-plans.show', $plan).'#comida-'.$meal->id)
            ->with('success', '"'.$meal->meal_name.'" se actualizó.');
    }

    public function destroy(DietPlan $plan, DietPlanMeal $meal)
    {
        Gate::authorize('update', $plan);

        // diet_plan_items se borran en cascada (FK ON DELETE CASCADE)
        $meal->delete();

        return redirect()
            ->route('diet-plans.show', $plan)
            ->with('success', '"'.$meal->meal_name.'" y sus alimentos se eliminaron.');
    }

    private function rules(): array
    {
        return [
            'meal_name' => ['required', 'string', 'max:100'],
            // diet_plan_meals.meal_time TIME (HH:MM) y notes VARCHAR(255), opcionales
            'meal_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function messages(): array
    {
        return [
            'meal_name.required' => 'Escribe el nombre del tiempo de comida.',
            'meal_name.max' => 'El nombre admite como máximo 100 caracteres.',
            'meal_time.date_format' => 'Escribe el horario en formato HH:MM.',
            'notes.max' => 'La indicación admite como máximo 255 caracteres.',
        ];
    }
}
