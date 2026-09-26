<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Catálogo de alérgenos (tabla `allergens`). */
class Allergen extends Model
{
    protected $fillable = ['name'];

    public function patients(): BelongsToMany
    {
        return $this->belongsToMany(Patient::class, 'patient_allergens')
            ->withPivot('severity')
            ->withTimestamps();
    }

    /** Pivote food_allergens: llave compuesta, sin timestamps. */
    public function foods(): BelongsToMany
    {
        return $this->belongsToMany(Food::class, 'food_allergens');
    }
}
