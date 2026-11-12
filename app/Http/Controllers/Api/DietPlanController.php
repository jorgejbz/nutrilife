<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DietPlan;
use App\Models\DietPlanDish;
use App\Models\DietPlanItem;
use App\Models\DietPlanMeal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DietPlanController extends Controller
{
    // GET /api/diet -> plan vigente con comidas, platillos, alimentos y macros
    public function show(Request $request): JsonResponse
    {
        $patient = $request->user();
        $today = now()->toDateString();
        $d = fn ($v) => $v ? substr((string) $v, 0, 10) : null;

        $candidates = DietPlan::where('patient_id', $patient->id)
            ->where('active', true)
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->get();

        // Prefiere el plan vigente hoy; si no hay, el más reciente
        $plan = $candidates->first(
            fn ($p) => $d($p->starts_at) <= $today && ($p->ends_at === null || $d($p->ends_at) >= $today)
        ) ?? $candidates->first();

        if (! $plan) {
            return response()->json(['message' => 'Sin plan activo'], 404);
        }

        $meals = DietPlanMeal::where('diet_plan_id', $plan->id)
            ->orderBy('meal_order')->orderBy('id')->get();

        $items = DietPlanItem::with(['food', 'dish'])
            ->whereIn('diet_plan_meal_id', $meals->pluck('id'))
            ->orderBy('id')->get()
            ->groupBy('diet_plan_meal_id');

        // Alergias del paciente que coinciden con alimentos del catálogo
        $patientAllergens = DB::table('patient_allergens')
            ->where('patient_id', $patient->id)->pluck('allergen_id');

        $foodAlerts = $patientAllergens->isEmpty()
            ? collect()
            : DB::table('food_allergens')
                ->join('allergens', 'allergens.id', '=', 'food_allergens.allergen_id')
                ->whereIn('food_allergens.allergen_id', $patientAllergens)
                ->get(['food_allergens.food_id', 'allergens.name'])
                ->groupBy('food_id')
                ->map(fn ($rows) => $rows->pluck('name')->unique()->values()->all());

        $totals = ['kcal' => 0, 'protein_g' => 0, 'carbs_g' => 0, 'lipids_g' => 0];

        $mealsOut = $meals->map(function ($meal) use ($items, &$totals, $foodAlerts) {
            $entries = [];
            $dishIndex = [];
            $count = 0;

            foreach ($items->get($meal->id, collect()) as $it) {
                $m = $this->mapItem($it, $foodAlerts);
                $count++;

                if ($it->diet_plan_dish_id) {
                    $k = $it->diet_plan_dish_id;
                    if (! isset($dishIndex[$k])) {
                        $dishIndex[$k] = count($entries);
                        $entries[] = [
                            'type' => 'dish',
                            'id' => $k,
                            'name' => $it->dish?->name ?? 'Platillo',
                            'image_url' => DietPlanDish::resolveImageUrl($it->dish?->image_url ?? $it->custom_image_url),
                            'image_credit' => $it->dish?->image_credit,
                            'kcal' => 0, 'protein_g' => 0, 'carbs_g' => 0, 'lipids_g' => 0,
                            'items' => [],
                        ];
                    }
                    $e = &$entries[$dishIndex[$k]];
                    $e['items'][] = $m;
                    foreach (['kcal', 'protein_g', 'carbs_g', 'lipids_g'] as $f) {
                        $e[$f] += $m[$f];
                    }
                    unset($e);
                } else {
                    $entries[] = [
                        'type' => 'food',
                        'id' => $it->id,
                        'name' => $m['name'],
                        'image_url' => $m['image_url'],
                        'image_credit' => null,
                        'kcal' => $m['kcal'], 'protein_g' => $m['protein_g'],
                        'carbs_g' => $m['carbs_g'], 'lipids_g' => $m['lipids_g'],
                        'items' => [$m],
                    ];
                }
            }

            $mk = ['kcal' => 0, 'protein_g' => 0, 'carbs_g' => 0, 'lipids_g' => 0];
            foreach ($entries as &$e) {
                foreach ($mk as $f => $_) {
                    $mk[$f] += $e[$f];
                    $e[$f] = round($e[$f], $f === 'kcal' ? 0 : 1);
                }
            }
            unset($e);
            foreach ($mk as $f => $v) {
                $totals[$f] += $v;
            }

            return [
                'id' => $meal->id,
                'name' => $meal->meal_name,
                'order' => (int) $meal->meal_order,
                'time' => $meal->meal_time ? substr((string) $meal->meal_time, 0, 5) : null,
                'notes' => $meal->notes,
                'kcal' => round($mk['kcal']),
                'items_count' => $count,
                'entries' => $entries,
            ];
        })->values();

        return response()->json([
            'id' => $plan->id,
            'name' => $plan->name,
            'goal' => $plan->goal,
            'instructions' => $plan->instructions,
            'starts_at' => $d($plan->starts_at),
            'ends_at' => $d($plan->ends_at),
            'target' => [
                'kcal' => (float) $plan->target_kcal,
                'protein_g' => (float) $plan->target_protein_g,
                'carbs_g' => (float) $plan->target_carbs_g,
                'lipids_g' => (float) $plan->target_lipids_g,
            ],
            'totals' => [
                'kcal' => round($totals['kcal']),
                'protein_g' => round($totals['protein_g'], 1),
                'carbs_g' => round($totals['carbs_g'], 1),
                'lipids_g' => round($totals['lipids_g'], 1),
            ],
            'meals' => $mealsOut,
        ]);
    }

    /** Renglón del catálogo (se calcula por gramos) o personalizado (valores ya guardados). */
    private function mapItem(DietPlanItem $i, $foodAlerts): array
    {
        // Misma regla de cálculo que el panel web (HasFoodOrCustomNutrients)
        $n = $i->nutrients();

        return [
            'id' => $i->id,
            'name' => $i->displayName(),
            'grams' => round((float) $i->grams, 1),
            'portion' => $i->food ? $i->food->portion_description : $i->custom_portion_description,
            'equivalents' => $i->food ? (float) $i->equivalents : null,
            'kcal' => round($n['kcal']),
            'protein_g' => round($n['protein_g'], 1),
            'carbs_g' => round($n['carbs_g'], 1),
            'lipids_g' => round($n['lipids_g'], 1),
            'image_url' => DietPlanDish::resolveImageUrl($i->custom_image_url),
            'allergy_alert' => $i->food_id ? $foodAlerts->get($i->food_id, []) : [],
        ];
    }
}
