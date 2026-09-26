<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Alimento dentro de un tiempo de comida (tabla `diet_plan_items`). */
class DietPlanItem extends Model
{
    protected $fillable = ['diet_plan_meal_id', 'food_id', 'equivalents', 'grams'];

    protected function casts(): array
    {
        return [
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
}
