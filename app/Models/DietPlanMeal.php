<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DietPlanMeal extends Model
{
    protected $fillable = [
        'diet_plan_id',
        'meal_name',
        'meal_order',
    ];

    protected function casts(): array
    {
        return [
            'meal_order' => 'integer',
        ];
    }

    public function dietPlan(): BelongsTo
    {
        return $this->belongsTo(DietPlan::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DietPlanItem::class);
    }
}