<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

<<<<<<< HEAD
/**
 * Alimento dentro de una comida (tabla `diet_plan_items`).
 *
 * En la BD definitiva food_id es NOT NULL: todo alimento sale del catálogo
 * `foods`. Se guardan los gramos indicados y sus equivalentes
 * (grams / foods.portion_grams, DECIMAL(4,2)). Las calorías y macros NO se
 * guardan: se calculan desde el catálogo con nutrients().
 */
class DietPlanItem extends Model
{
    /** Límites de las columnas DECIMAL de la BD. */
    public const MAX_GRAMS = 9999.99;      // DECIMAL(6,2)

    public const MAX_EQUIVALENTS = 99.99;  // DECIMAL(4,2)

    protected $fillable = [
        'diet_plan_meal_id',
        'food_id',
=======
class DietPlanItem extends Model
{
    protected $fillable = [
        'diet_plan_meal_id',
        'food_id',

        // Alimento personalizado
        'custom_name',
        'custom_kcal',
        'custom_protein_g',
        'custom_carbs_g',
        'custom_lipids_g',
        'custom_portion_description',

>>>>>>> 56f1ba86bc25aa2407be1779b09730fe5ce1b82e
        'equivalents',
        'grams',
    ];

    protected function casts(): array
    {
        return [
            'diet_plan_meal_id' => 'integer',
            'food_id' => 'integer',
            'equivalents' => 'decimal:2',
            'grams' => 'decimal:2',

            'custom_kcal' => 'decimal:2',
            'custom_protein_g' => 'decimal:2',
            'custom_carbs_g' => 'decimal:2',
            'custom_lipids_g' => 'decimal:2',
        ];
    }

    public function meal(): BelongsTo
    {
        return $this->belongsTo(
            DietPlanMeal::class,
            'diet_plan_meal_id'
        );
    }

    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }

<<<<<<< HEAD
    /**
     * Equivalentes de un alimento para cierta cantidad de gramos.
     * null si el alimento no tiene una porción válida.
     */
    public static function equivalentsFor(Food $food, float $grams): ?float
    {
        $portion = (float) $food->portion_grams;

        return $portion > 0 ? round($grams / $portion, 2) : null;
    }

    /**
     * Aporte del alimento según los gramos indicados. Los valores de
     * `foods` son por porción (portion_grams).
     *
     * @return array{kcal: float, protein_g: float, carbs_g: float, lipids_g: float}
     */
    public function nutrients(): array
    {
        $food = $this->food;
        $portion = (float) ($food?->portion_grams ?? 0);

        if (! $food || $portion <= 0) {
            return self::emptyNutrients();
        }

        $factor = (float) $this->grams / $portion;

        return [
            'kcal' => (float) $food->kcal * $factor,
            'protein_g' => (float) $food->protein_g * $factor,
            'carbs_g' => (float) $food->carbs_g * $factor,
            'lipids_g' => (float) $food->lipids_g * $factor,
        ];
    }

    /**
     * @return array{kcal: float, protein_g: float, carbs_g: float, lipids_g: float}
     */
    public static function emptyNutrients(): array
    {
        return ['kcal' => 0.0, 'protein_g' => 0.0, 'carbs_g' => 0.0, 'lipids_g' => 0.0];
    }

    /**
     * Suma una colección de arreglos de nutrientes.
     *
     * @param  Collection<int, array<string, float>>  $rows
     * @return array{kcal: float, protein_g: float, carbs_g: float, lipids_g: float}
     */
    public static function sumNutrients(Collection $rows): array
    {
        $total = self::emptyNutrients();

        foreach ($rows as $row) {
            foreach ($total as $key => $value) {
                $total[$key] = $value + (float) ($row[$key] ?? 0);
            }
        }

        return $total;
    }
}
=======
    /*
    |--------------------------------------------------------------------------
    | SABER SI ES PERSONALIZADO
    |--------------------------------------------------------------------------
    */

    public function isCustom(): bool
    {
        return is_null($this->food_id);
    }
}
>>>>>>> 56f1ba86bc25aa2407be1779b09730fe5ce1b82e
