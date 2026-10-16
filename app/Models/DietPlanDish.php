<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DietPlanDish extends Model
{
    protected $table = 'diet_plan_dishes';

    protected $fillable = [
        'id',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(DietPlanItem::class, 'diet_plan_dish_id');
    }
}