<?php

namespace App\Models\Concerns;

use App\Models\DietPlanItem;

/**
 * Renglón que es un alimento del catálogo (food_id) o uno personalizado
 * (custom_name + custom_*), nunca ambos: lo garantizan los CHECK
 * *_food_or_custom_check (actualización 3.2). Lo usan `diet_plan_items` y
 * `diet_plan_dish_items`.
 *
 * - Del catálogo: kcal y macros se calculan de `foods` (valores por porción).
 * - Personalizado: custom_kcal y macros son el aporte TOTAL del renglón para
 *   sus `grams`. NULL = sin dato (cuenta 0 y se avisa con hasNutrients()).
 */
trait HasFoodOrCustomNutrients
{
    public function isCustom(): bool
    {
        return $this->food_id === null;
    }

    public function displayName(): string
    {
        return $this->food?->name ?? $this->custom_name ?? 'Alimento sin catálogo';
    }

    /**
     * Aporte del renglón según sus gramos.
     *
     * @return array{kcal: float, protein_g: float, carbs_g: float, lipids_g: float}
     */
    public function nutrients(): array
    {
        if ($this->isCustom()) {
            return array_map(
                fn (string $column) => (float) ($this->{$column} ?? 0),
                DietPlanItem::CUSTOM_NUTRIENTS
            );
        }

        $food = $this->food;
        $portion = (float) ($food?->portion_grams ?? 0);

        if (! $food || $portion <= 0) {
            return DietPlanItem::emptyNutrients();
        }

        $factor = (float) $this->grams / $portion;

        return [
            'kcal' => (float) $food->kcal * $factor,
            'protein_g' => (float) $food->protein_g * $factor,
            'carbs_g' => (float) $food->carbs_g * $factor,
            'lipids_g' => (float) $food->lipids_g * $factor,
        ];
    }

    /** false si es personalizado y le falta algún valor (kcal o macros en NULL). */
    public function hasNutrients(): bool
    {
        if (! $this->isCustom()) {
            return $this->food !== null;
        }

        foreach (DietPlanItem::CUSTOM_NUTRIENTS as $column) {
            if ($this->{$column} === null) {
                return false;
            }
        }

        return true;
    }
}
