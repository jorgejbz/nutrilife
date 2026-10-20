<?php

namespace App\Policies;

use App\Models\DietPlan;
use App\Models\User;

/**
 * Permisos de Planes alimenticios (tabla diet_plans).
 *
 * Dos condiciones, siempre juntas:
 *  1. El plan contratado incluye la función (User::hasFeature('diet_plans'):
 *     Pro, Consultorio o prueba gratuita vigente).
 *  2. El plan alimenticio es del nutriólogo (diet_plans.user_id). Así cada
 *     cuenta —incluidas las de Consultorio— solo ve y modifica lo suyo.
 *
 * Las comidas (diet_plan_meals) y alimentos (diet_plan_items) no tienen
 * user_id: heredan el permiso del plan al que pertenecen (las rutas usan
 * scopeBindings para que una comida/alimento de otro plan dé 404).
 */
class DietPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasFeature('diet_plans');
    }

    public function create(User $user): bool
    {
        return $user->hasFeature('diet_plans');
    }

    public function view(User $user, DietPlan $dietPlan): bool
    {
        return $this->owns($user, $dietPlan);
    }

    public function update(User $user, DietPlan $dietPlan): bool
    {
        return $this->owns($user, $dietPlan);
    }

    public function delete(User $user, DietPlan $dietPlan): bool
    {
        return $this->owns($user, $dietPlan);
    }

    private function owns(User $user, DietPlan $dietPlan): bool
    {
        return $user->hasFeature('diet_plans')
            && (int) $dietPlan->user_id === (int) $user->id;
    }
}
