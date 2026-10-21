<?php

namespace App\Models;

use App\Models\Concerns\HasFoodOrCustomNutrients;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Alimento dentro de una comida (tabla `diet_plan_items`).
 *
 * Desde la actualización 3.2 un renglón es del catálogo `foods` (food_id;
 * kcal y macros se calculan del catálogo y NO se guardan) o personalizado
 * (food_id NULL + custom_*, aporte total de sus gramos): ver
 * HasFoodOrCustomNutrients. Se guardan los gramos y sus equivalentes
 * (grams / foods.portion_grams, DECIMAL(4,2); 1 para personalizados).
 * diet_plan_dish_id = platillo del que salió el renglón (copia).
 */
class DietPlanItem extends Model
{
    use HasFoodOrCustomNutrients;

    /** Límites de las columnas DECIMAL de la BD. */
    public const MAX_GRAMS = 9999.99;      // DECIMAL(6,2)

    public const MAX_EQUIVALENTS = 99.99;  // DECIMAL(4,2)

    /**
     * Columnas del aporte de un personalizado (custom_kcal DECIMAL(7,2) y
     * macros DECIMAL(6,2): mismos límites que DietPlan::MAX_KCAL / MAX_MACRO_G).
     */
    public const CUSTOM_NUTRIENTS = [
        'kcal' => 'custom_kcal',
        'protein_g' => 'custom_protein_g',
        'carbs_g' => 'custom_carbs_g',
        'lipids_g' => 'custom_lipids_g',
    ];

    protected $fillable = [
        'diet_plan_meal_id',
        'diet_plan_dish_id',
        'food_id',
        // Alimento personalizado (actualización 3.2)
        'custom_name',
        'custom_kcal',
        'custom_protein_g',
        'custom_carbs_g',
        'custom_lipids_g',
        'custom_portion_description',
        'custom_image_url',
        'equivalents',
        'grams',
    ];

    protected function casts(): array
    {
        return [
            'diet_plan_meal_id' => 'integer',
            'diet_plan_dish_id' => 'integer',
            'food_id' => 'integer',
            'custom_kcal' => 'decimal:2',
            'custom_protein_g' => 'decimal:2',
            'custom_carbs_g' => 'decimal:2',
            'custom_lipids_g' => 'decimal:2',
            'equivalents' => 'decimal:2',
            'grams' => 'decimal:2',
        ];
    }

    public function meal(): BelongsTo
    {
        return $this->belongsTo(DietPlanMeal::class, 'diet_plan_meal_id');
    }

    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }

    /** URL de la foto copiada del platillo de origen (null si no tiene). */
    public function imageSrc(): ?string
    {
        return DietPlanDish::resolveImageUrl($this->custom_image_url);
    }

    /** Platillo de origen (NULL si se agregó suelto o si el platillo se borró). */
    public function dish(): BelongsTo
    {
        return $this->belongsTo(DietPlanDish::class, 'diet_plan_dish_id');
    }

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
     * Personalizado: su aporte es el de sus gramos, así que al cambiar la
     * cantidad se reescala en proporción (NULL sigue NULL).
     *
     * @return array<string, float|null> custom_* => valor
     */
    public function customNutrientsFor(float $grams): array
    {
        $current = (float) $this->grams;
        $factor = $current > 0 ? $grams / $current : 1.0;
        $scaled = [];

        foreach (self::CUSTOM_NUTRIENTS as $column) {
            $scaled[$column] = $this->{$column} === null ? null : round((float) $this->{$column} * $factor, 2);
        }

        return $scaled;
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
