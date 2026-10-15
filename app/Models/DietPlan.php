<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DietPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id',
        'user_id',
        'name',
        'target_kcal',
        'target_protein_g',
        'target_lipids_g',
        'target_carbs_g',
        'starts_at',
        'ends_at',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'active' => 'boolean',

            'target_kcal' => 'decimal:2',
            'target_protein_g' => 'decimal:2',
            'target_lipids_g' => 'decimal:2',
            'target_carbs_g' => 'decimal:2',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function meals(): HasMany
    {
        return $this->hasMany(DietPlanMeal::class)
            ->orderBy('meal_order');
    }
}