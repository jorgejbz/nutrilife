<?php

namespace App\Services;

use App\Models\FoodPathologyRestriction;
use App\Models\Patient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Avisos clínicos al armar un plan, con las relaciones que ya existen en
 * la BD:
 *  - patient_allergens  × food_allergens              → "Contiene: Huevo"
 *  - patient_pathologies × food_pathology_restrictions → "Prohibido / Limitado
 *    por Diabetes…" (la restricción puede ser de un alimento o de un grupo).
 *
 * Solo informa: la decisión clínica es del nutriólogo, no se bloquea nada.
 */
class DietPlanFoodWarnings
{
    /**
     * @param  Collection<int, \App\Models\Food>  $foods
     * @return array<int, list<array{type: string, text: string}>> food_id => avisos
     */
    public static function forPatient(?Patient $patient, Collection $foods): array
    {
        if (! $patient || $foods->isEmpty()) {
            return [];
        }

        $patient->loadMissing(['allergens', 'pathologies']);

        $warnings = [];
        $foodIds = $foods->pluck('id')->all();

        // Alérgenos del paciente presentes en el alimento
        $allergens = $patient->allergens->keyBy('id');

        if ($allergens->isNotEmpty()) {
            DB::table('food_allergens')
                ->whereIn('food_id', $foodIds)
                ->whereIn('allergen_id', $allergens->keys()->all())
                ->get()
                ->each(function ($row) use (&$warnings, $allergens) {
                    $allergen = $allergens->get($row->allergen_id);
                    $severity = $allergen?->pivot?->severity;

                    $warnings[(int) $row->food_id][] = [
                        'type' => 'allergen',
                        'text' => 'Contiene '.$allergen?->name.($severity ? ' (alergia '.$severity.')' : ''),
                    ];
                });
        }

        // Restricciones por patología (por alimento o por grupo)
        $pathologies = $patient->pathologies->keyBy('id');

        if ($pathologies->isNotEmpty()) {
            $foodsByGroup = $foods->groupBy('food_group_id');

            FoodPathologyRestriction::whereIn('pathology_id', $pathologies->keys()->all())
                ->get()
                ->each(function (FoodPathologyRestriction $restriction) use (&$warnings, $pathologies, $foodsByGroup, $foodIds) {
                    $targets = $restriction->food_id
                        ? (in_array((int) $restriction->food_id, $foodIds, true) ? [(int) $restriction->food_id] : [])
                        : $foodsByGroup->get($restriction->food_group_id, collect())->pluck('id')->all();

                    $pathology = $pathologies->get($restriction->pathology_id)?->name;
                    $text = ($restriction->restriction_type === 'prohibido' ? 'Prohibido' : 'Limitado')
                        .' por '.$pathology
                        .($restriction->max_portions_per_day !== null
                            ? ' (máx. '.rtrim(rtrim((string) $restriction->max_portions_per_day, '0'), '.').' porciones/día)'
                            : '');

                    foreach ($targets as $foodId) {
                        $warnings[(int) $foodId][] = [
                            'type' => $restriction->restriction_type === 'prohibido' ? 'forbidden' : 'limited',
                            'text' => $text,
                        ];
                    }
                });
        }

        return $warnings;
    }
}
