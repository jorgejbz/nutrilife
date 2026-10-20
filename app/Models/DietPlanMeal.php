<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tiempo de comida de un plan (tabla `diet_plan_meals`): Desayuno,
 * Colación, Comida… meal_order es TINYINT UNSIGNED (1–255).
 * Se borra en cascada con su plan; sus alimentos se borran con ella.
 */
class DietPlanMeal extends Model
{
    /** Sugerencias para el nombre (el campo es libre, VARCHAR(100)). */
    public const SUGGESTED_NAMES = [
        'Desayuno',
        'Colación matutina',
        'Comida',
        'Colación vespertina',
        'Cena',
    ];

    /** meal_order es TINYINT UNSIGNED. */
    public const MAX_ORDER = 255;

    protected $fillable = [
        'diet_plan_id',
        'meal_name',
        'meal_order',
        'meal_time',   // TIME, opcional (actualización 3.1)
        'notes',       // indicación de la comida, opcional (actualización 3.1)
    ];

    protected function casts(): array
    {
        return [
            'diet_plan_id' => 'integer',
            'meal_order' => 'integer',
        ];
    }

    public function dietPlan(): BelongsTo
    {
        return $this->belongsTo(DietPlan::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DietPlanItem::class)->orderBy('id');
    }

    /** Horario "HH:MM" para mostrar (meal_time es TIME). */
    public function timeLabel(): ?string
    {
        return $this->meal_time ? substr((string) $this->meal_time, 0, 5) : null;
    }

    /**
     * Aporte de la comida (suma de sus alimentos).
     *
     * @return array{kcal: float, protein_g: float, carbs_g: float, lipids_g: float}
     */
    public function totals(): array
    {
        return DietPlanItem::sumNutrients(
            $this->items->map(fn (DietPlanItem $item) => $item->nutrients())
        );
    }

    /** Icono según el nombre de la comida. */
    public function icon(): string
    {
        $name = mb_strtolower($this->meal_name);

        return match (true) {
            str_contains($name, 'desay') => 'fas fa-mug-hot',
            str_contains($name, 'colaci') => 'fas fa-apple-alt',
            str_contains($name, 'cena') => 'fas fa-moon',
            default => 'fas fa-utensils',
        };
    }
}
