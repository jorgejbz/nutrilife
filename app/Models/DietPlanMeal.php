<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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

    /**
     * Renglones para mostrar: los alimentos sueltos uno por uno y los
     * ingredientes copiados de un platillo juntos (por diet_plan_dish_id),
     * en el lugar donde aparece su primer ingrediente. Si el platillo de
     * origen se borró (diet_plan_dish_id NULL) sus ingredientes quedan sueltos.
     *
     * @return Collection<int, array{dish_id: int|null, items: Collection<int, DietPlanItem>}>
     */
    public function entries(): Collection
    {
        $entries = [];
        $groupIndex = [];

        foreach ($this->items as $item) {
            $dishId = $item->diet_plan_dish_id;

            if ($dishId === null) {
                $entries[] = ['dish_id' => null, 'items' => collect([$item])];

                continue;
            }

            if (! isset($groupIndex[$dishId])) {
                $groupIndex[$dishId] = count($entries);
                $entries[] = ['dish_id' => $dishId, 'items' => collect()];
            }

            $entries[$groupIndex[$dishId]]['items']->push($item);
        }

        return collect($entries);
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
