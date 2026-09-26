<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Catálogo de patologías (tabla `pathologies`). */
class Pathology extends Model
{
    protected $fillable = ['name', 'description'];

    public function patients(): BelongsToMany
    {
        return $this->belongsToMany(Patient::class, 'patient_pathologies')
            ->withPivot(['diagnosed_at', 'notes'])
            ->withTimestamps();
    }

    public function foodRestrictions(): HasMany
    {
        return $this->hasMany(FoodPathologyRestriction::class);
    }
}
