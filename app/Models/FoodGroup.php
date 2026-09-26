<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Grupos de alimentos SMAE / INCAP (tabla `food_groups`). */
class FoodGroup extends Model
{
    protected $fillable = ['name'];

    public function foods(): HasMany
    {
        return $this->hasMany(Food::class);
    }
}
