<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

/**
 * Planes de NutriLife (tabla subscription_plans).
 *
 * Solo INSERTA/ACTUALIZA filas; no cambia la estructura de la BD.
 * La prueba gratuita de 7 días necesita al menos un plan activo porque
 * subscriptions.subscription_plan_id es obligatorio.
 *
 * Precios: los mismos que muestra la landing (sección "Planes").
 * max_patients: Esencial 50, Pro 200, Consultorio sin límite (null).
 * Ver docs/GLOSARIO_PLANES.md.
 *
 * Se puede ejecutar varias veces sin duplicar: busca el plan por nombre.
 *
 *   php artisan db:seed --class=SubscriptionPlanSeeder
 */
class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            // max_patients: único límite que guarda la BD (null = sin límite)
            ['name' => 'Esencial', 'price' => 249.00, 'max_patients' => 50],
            ['name' => 'Pro', 'price' => 399.00, 'max_patients' => 200],
            ['name' => 'Consultorio', 'price' => 599.00, 'max_patients' => null],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['name' => $plan['name']],
                [
                    'price' => $plan['price'],
                    'billing_period' => SubscriptionPlan::PERIOD_MONTHLY,
                    'max_patients' => $plan['max_patients'],
                    'active' => true,
                ]
            );
        }
    }
}
