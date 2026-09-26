<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Alimento / equivalente (tabla `foods`). */
class Food extends Model
{
    /** "food" es incontable para el pluralizador; se fija el nombre real. */
    protected $table = 'foods';

    protected $fillable = [
        'food_group_id', 'name', 'portion_grams', 'portion_description',
        'kcal', 'protein_g', 'lipids_g', 'carbs_g', 'fiber_g', 'sodium_mg',
        'glycemic_index', 'source', 'active',
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
            'glycemic_index' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(FoodGroup::class, 'food_group_id');
    }

    public function allergens(): BelongsToMany
    {
        return $this->belongsToMany(Allergen::class, 'food_allergens');
    }
}
