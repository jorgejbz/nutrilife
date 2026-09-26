<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Restricción de un alimento O de un grupo completo para una patología
 * (tabla `food_pathology_restrictions`). La BD exige exactamente uno de
 * food_id / food_group_id (CHECK fpr_food_or_group_check).
 * restriction_type: prohibido | limitado
 */
class FoodPathologyRestriction extends Model
{
    protected $fillable = [
        'pathology_id', 'food_id', 'food_group_id',
        'restriction_type', 'max_portions_per_day', 'notes',
    ];

    protected function casts(): array
    {
        return ['max_portions_per_day' => 'decimal:1'];
    }

    public function pathology(): BelongsTo
    {
        return $this->belongsTo(Pathology::class);
    }

    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }

    public function foodGroup(): BelongsTo
    {
        return $this->belongsTo(FoodGroup::class);
    }
}
