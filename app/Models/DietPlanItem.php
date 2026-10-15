<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

        'equivalents',
        'grams',
    ];

    protected function casts(): array
    {
        return [
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