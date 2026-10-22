<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Retira el platillo de EJEMPLO «Ensalada de Nopales con Queso Panela».
 *
 * Lo creaba DemoAccountsSeeder como "platillo propio" de la cuenta de
 * demostración pro@nutrilife.test (ya no lo crea). Solo se borra esa fila
 * (nombre exacto + dueño = esa cuenta), nada más:
 *  - sus ingredientes (diet_plan_dish_items) se borran en cascada (FK);
 *  - los renglones de planes que salieron de él (diet_plan_items) se
 *    conservan: la FK diet_plan_dish_id es nullOnDelete y los valores ya
 *    estaban copiados, así que el plan del paciente no cambia.
 * Los platillos del sistema y los de otros usuarios no se tocan.
 */
return new class extends Migration
{
    private const DISH = 'Ensalada de Nopales con Queso Panela';

    private const DEMO_OWNER = 'pro@nutrilife.test';

    public function up(): void
    {
        $ownerId = DB::table('users')->where('email', self::DEMO_OWNER)->value('id');

        if (! $ownerId) {
            return;
        }

        $dishIds = DB::table('diet_plan_dishes')
            ->where('user_id', $ownerId)
            ->where('name', self::DISH)
            ->pluck('id');

        if ($dishIds->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($dishIds) {
            // Explícito (además de la FK) por si la BD se importó sin las reglas ON DELETE.
            DB::table('diet_plan_items')->whereIn('diet_plan_dish_id', $dishIds)->update(['diet_plan_dish_id' => null]);
            DB::table('diet_plan_dish_items')->whereIn('diet_plan_dish_id', $dishIds)->delete();
            DB::table('diet_plan_dishes')->whereIn('id', $dishIds)->delete();
        });
    }

    public function down(): void
    {
        // Dato de ejemplo retirado a propósito: no se vuelve a crear.
    }
};
