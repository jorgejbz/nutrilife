<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Nuevos límites de pacientes: Esencial 25 (antes 50) y Pro 100 (antes 200).
 * Consultorio sigue sin límite.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('subscription_plans')->where('name', 'Esencial')->update(['max_patients' => 25]);
        DB::table('subscription_plans')->where('name', 'Pro')->update(['max_patients' => 100]);
    }

    public function down(): void
    {
        DB::table('subscription_plans')->where('name', 'Esencial')->update(['max_patients' => 50]);
        DB::table('subscription_plans')->where('name', 'Pro')->update(['max_patients' => 200]);
    }
};
