<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
<<<<<<< HEAD
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo de alimentos (tabla `foods`, fuente por defecto INCAP-SMAE).
 * kcal y macros son por porción (portion_grams). Es un catálogo global:
 * no pertenece a ningún nutriólogo.
 */
class Food extends Model
{
=======
use Illuminate\Database\Eloquent\Relations\HasMany;

class Food extends Model
{

>>>>>>> 56f1ba86bc25aa2407be1779b09730fe5ce1b82e
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
            'food_group_id' => 'integer',
            'glycemic_index' => 'integer',
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
<<<<<<< HEAD
    {
        return $this->hasMany(DietPlanItem::class);
    }

    /** Pivote food_allergens (llave compuesta, sin timestamps). */
    public function allergens(): BelongsToMany
=======
>>>>>>> 56f1ba86bc25aa2407be1779b09730fe5ce1b82e
    {
        return $this->hasMany(DietPlanItem::class);
    }
<<<<<<< HEAD

    /** Restricciones directas del alimento por patología. */
    public function pathologyRestrictions(): HasMany
    {
        return $this->hasMany(FoodPathologyRestriction::class);
    }
=======
>>>>>>> 56f1ba86bc25aa2407be1779b09730fe5ce1b82e
}