<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Si la BD se importó desde proyecto_v3.sql, la tabla ya existe.
        if (Schema::hasTable('roles')) {
            return;
        }

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('description', 255);
        });

        // Roles base del sistema
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'nutriologo', 'description' => 'Profesional que gestiona pacientes, citas, evaluaciones y dietas'],
            ['id' => 2, 'name' => 'admin', 'description' => 'Administrador de la plataforma: ve suscripciones, ingresos y métricas globales'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
