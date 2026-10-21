<?php

namespace App\Models;

use App\Models\Concerns\HasFoodOrCustomNutrients;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ingrediente de un platillo (tabla `diet_plan_dish_items`, actualización 3.2).
 *
 * Del catálogo (food_id) o personalizado (custom_*): ver
 * HasFoodOrCustomNutrients. Precisión igual a `foods` y a diet_plan_items:
 * grams DECIMAL(6,2), custom_kcal DECIMAL(7,2), macros DECIMAL(6,2).
 */
class DietPlanDishItem extends Model
{
    use HasFoodOrCustomNutrients;

    protected $fillable = [
        'diet_plan_dish_id',
        'food_id',
        'custom_name',
        'custom_kcal',
        'custom_protein_g',
        'custom_carbs_g',
        'custom_lipids_g',
        'grams',
    ];

    protected function casts(): array
    {
        return [
            'diet_plan_dish_id' => 'integer',
            'food_id' => 'integer',
            'custom_kcal' => 'decimal:2',
            'custom_protein_g' => 'decimal:2',
            'custom_carbs_g' => 'decimal:2',
            'custom_lipids_g' => 'decimal:2',
            'grams' => 'decimal:2',
        ];
    }

    public function dish(): BelongsTo
    {
        return $this->belongsTo(DietPlanDish::class, 'diet_plan_dish_id');
    }

    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }
}
