<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Planes alimenticios (cambio SOLO aditivo, columnas opcionales):
 * - diet_plans.goal          → objetivo general del plan.
 * - diet_plans.instructions  → indicaciones y observaciones del nutriólogo.
 * - diet_plan_meals.meal_time → horario sugerido de la comida.
 * - diet_plan_meals.notes     → indicación corta de esa comida.
 *
 * Equivalente en SQL: database/sql/actualizacion_v3_1.sql
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diet_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('diet_plans', 'goal')) {
                $table->string('goal', 255)->nullable()->after('name');
            }

            if (! Schema::hasColumn('diet_plans', 'instructions')) {
                $table->text('instructions')->nullable()->after('goal');
            }
        });

        Schema::table('diet_plan_meals', function (Blueprint $table) {
            if (! Schema::hasColumn('diet_plan_meals', 'meal_time')) {
                $table->time('meal_time')->nullable()->after('meal_order');
            }

            if (! Schema::hasColumn('diet_plan_meals', 'notes')) {
                $table->string('notes', 255)->nullable()->after('meal_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('diet_plans', function (Blueprint $table) {
            $table->dropColumn(array_values(array_filter(['goal', 'instructions'], fn ($c) => Schema::hasColumn('diet_plans', $c))));
        });

        Schema::table('diet_plan_meals', function (Blueprint $table) {
            $table->dropColumn(array_values(array_filter(['meal_time', 'notes'], fn ($c) => Schema::hasColumn('diet_plan_meals', $c))));
        });
    }
};
