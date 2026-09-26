<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Si la BD se importó desde proyecto_v3.sql, la tabla ya existe.
        if (Schema::hasTable('diet_plan_items')) {
            return;
        }

        Schema::create('diet_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diet_plan_meal_id')->constrained('diet_plan_meals')->cascadeOnDelete();
            $table->foreignId('food_id')->constrained('foods');
            $table->decimal('equivalents', 4, 2)->default(1.00)
                ->comment('Cantidad de equivalentes, ej. 1.5');
            $table->decimal('grams', 6, 2)
                ->comment('Gramaje final (equivalents * foods.portion_grams, ajustable a mano)');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diet_plan_items');
    }
};
