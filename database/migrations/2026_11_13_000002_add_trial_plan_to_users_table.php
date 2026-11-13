<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * La prueba gratuita ahora es del paquete que el nutriólogo elige probar
 * (Esencial o Pro) y da solo las funciones de ese paquete.
 *
 * - users.trial_plan: el paquete elegido al registrarse (se usa al activar
 *   la cuenta, cuando empieza la prueba).
 * - Las pruebas que ya están en curso se pasan a Pro: antes la prueba daba
 *   todas las funciones, así que nadie pierde acceso a la mitad de su prueba.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('trial_plan', 30)->nullable()->after('degree_number');
        });

        $proId = DB::table('subscription_plans')->where('name', 'Pro')->value('id');

        if ($proId) {
            DB::table('subscriptions')->where('status', 'en_prueba')->update(['subscription_plan_id' => $proId]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('trial_plan');
        });
    }
};
