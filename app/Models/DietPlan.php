<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Plan alimenticio de un paciente (tabla `diet_plans`).
 *
 * user_id = nutriólogo dueño del plan (también es dueño del paciente).
 * Objetivos diarios: target_kcal DECIMAL(7,2) y macros DECIMAL(6,2).
 * Comidas → diet_plan_meals (cascade) → alimentos diet_plan_items (cascade).
 *
 * Disponible solo en los planes Pro, Consultorio y en la prueba gratuita
 * (config/nutrilife.php → features.diet_plans, App\Policies\DietPlanPolicy).
 */
class DietPlan extends Model
{
    use SoftDeletes;

    /** Límites de las columnas DECIMAL de la BD. */
    public const MAX_KCAL = 99999.99;    // DECIMAL(7,2)

    public const MAX_MACRO_G = 9999.99;  // DECIMAL(6,2)

    protected $fillable = [
        'patient_id',
        'user_id',
        'name',
        'goal',          // objetivo general (actualización 3.1)
        'instructions',  // indicaciones del nutriólogo (actualización 3.1)
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
            'patient_id' => 'integer',
            'user_id' => 'integer',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'active' => 'boolean',
            'target_kcal' => 'decimal:2',
            'target_protein_g' => 'decimal:2',
            'target_lipids_g' => 'decimal:2',
            'target_carbs_g' => 'decimal:2',
        ];
    }

    /* ------------------------------------------------------------------
     | Relaciones
     * ------------------------------------------------------------------ */

    /** withTrashed: el plan conserva a su paciente aunque se haya dado de baja. */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function meals(): HasMany
    {
        return $this->hasMany(DietPlanMeal::class)
            ->orderBy('meal_order')
            ->orderBy('id');
    }

    /* ------------------------------------------------------------------
     | Scopes
     * ------------------------------------------------------------------ */

    public function scopeOwnedBy(Builder $query, User|int $user): Builder
    {
        return $query->where('user_id', $user instanceof User ? $user->id : $user);
    }

    /* ------------------------------------------------------------------
     | Cálculos
     * ------------------------------------------------------------------ */

    /**
     * Aporte total del día (suma de todas las comidas).
     *
     * @return array{kcal: float, protein_g: float, carbs_g: float, lipids_g: float}
     */
    public function totals(): array
    {
        return DietPlanItem::sumNutrients(
            $this->meals->map(fn (DietPlanMeal $meal) => $meal->totals())
        );
    }

    /**
     * Objetivos del plan como números.
     *
     * @return array{kcal: float, protein_g: float, carbs_g: float, lipids_g: float}
     */
    public function targets(): array
    {
        return [
            'kcal' => (float) $this->target_kcal,
            'protein_g' => (float) $this->target_protein_g,
            'carbs_g' => (float) $this->target_carbs_g,
            'lipids_g' => (float) $this->target_lipids_g,
        ];
    }

    /**
     * Avance contra el objetivo, en porcentaje (0 si no hay objetivo).
     *
     * @return array{kcal: float, protein_g: float, carbs_g: float, lipids_g: float}
     */
    public function progress(): array
    {
        $totals = $this->totals();
        $progress = [];

        foreach ($this->targets() as $key => $target) {
            $progress[$key] = $target > 0 ? round($totals[$key] / $target * 100, 1) : 0.0;
        }

        return $progress;
    }

    /** "Activo" / "Finalizado" (diet_plans.active). */
    public function statusLabel(): string
    {
        return $this->active ? 'Activo' : 'Finalizado';
    }
}
