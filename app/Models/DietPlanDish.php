<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Platillo reutilizable (tabla `diet_plan_dishes`, actualización 3.2).
 *
 * user_id NULL = platillo del sistema (lo ven todas las cuentas); con valor
 * = platillo propio del nutriólogo (aislado por user_id, como pacientes y
 * planes). Sus ingredientes están en `diet_plan_dish_items` (cascade).
 *
 * Al agregarlo a una comida, sus ingredientes se COPIAN a diet_plan_items:
 * editar o borrar el platillo no cambia los planes ya hechos (su
 * diet_plan_dish_id queda en NULL).
 */
class DietPlanDish extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'image_url',     // URL externa o ruta dentro de public/ (images/platillos/…)
        'image_credit',  // «Autor · Licencia» de la foto (actualización 3.3)
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DietPlanDishItem::class)->orderBy('id');
    }

    /** Platillos del sistema + los propios del nutriólogo. */
    public function scopeVisibleTo(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->where(fn (Builder $query) => $query
            ->whereNull('user_id')
            ->orWhere('user_id', $userId));
    }

    public function isSystem(): bool
    {
        return $this->user_id === null;
    }

    /** URL de la foto para <img src> (null si no tiene). */
    public function imageSrc(): ?string
    {
        return self::resolveImageUrl($this->image_url);
    }

    /**
     * Las fotos del sistema se guardan como ruta dentro de public/
     * (images/platillos/…); las demás pueden ser URL completas. asset()
     * respeta la carpeta donde esté publicada la app (XAMPP o artisan serve).
     */
    public static function resolveImageUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        return preg_match('#^(https?:)?//#i', $url) ? $url : asset(ltrim($url, '/'));
    }

    /**
     * Aporte del platillo (suma de sus ingredientes; los que no tienen datos
     * cuentan 0, ver missingNutrientsCount()).
     *
     * @return array{kcal: float, protein_g: float, carbs_g: float, lipids_g: float}
     */
    public function totals(): array
    {
        return DietPlanItem::sumNutrients(
            $this->items->map(fn (DietPlanDishItem $item) => $item->nutrients())
        );
    }

    /**
     * true si los ingredientes copiados en una comida ya no coinciden con la
     * receta (se quitó, agregó, sustituyó o cambió la cantidad de alguno).
     *
     * @param  Collection<int, DietPlanItem>  $copied
     */
    public function differsFrom(Collection $copied): bool
    {
        $signature = fn (Collection $rows) => $rows
            ->map(fn ($row) => ($row->food_id ? 'f'.$row->food_id : 'c'.mb_strtolower((string) $row->custom_name))
                .'@'.number_format((float) $row->grams, 2, '.', ''))
            ->sort()
            ->values()
            ->all();

        return $signature($this->items) !== $signature($copied);
    }

    /** Ingredientes personalizados sin kcal o macros. */
    public function missingNutrientsCount(): int
    {
        return $this->items->reject(fn (DietPlanDishItem $item) => $item->hasNutrients())->count();
    }
}
