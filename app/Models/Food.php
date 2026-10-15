<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Food extends Model
{

    protected $table = 'foods';
    protected $fillable = [
        'food_group_id',
        'name',
        'portion_grams',
        'portion_description',
        'kcal',
        'protein_g',
        'lipids_g',
        'carbs_g',
        'fiber_g',
        'sodium_mg',
        'glycemic_index',
        'source',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'portion_grams' => 'decimal:2',
            'kcal' => 'decimal:2',
            'protein_g' => 'decimal:2',
            'lipids_g' => 'decimal:2',
            'carbs_g' => 'decimal:2',
            'fiber_g' => 'decimal:2',
            'sodium_mg' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    public function foodGroup(): BelongsTo
    {
        return $this->belongsTo(FoodGroup::class);
    }

    public function dietPlanItems(): HasMany
    {
        return $this->hasMany(DietPlanItem::class);
    }
}