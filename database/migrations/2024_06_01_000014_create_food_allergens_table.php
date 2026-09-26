<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Si la BD se importó desde proyecto_v3.sql, la tabla ya existe.
        if (Schema::hasTable('food_allergens')) {
            return;
        }

        Schema::create('food_allergens', function (Blueprint $table) {
            $table->foreignId('food_id')->constrained('foods')->cascadeOnDelete();
            $table->foreignId('allergen_id')->constrained('allergens')->cascadeOnDelete();

            $table->primary(['food_id', 'allergen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_allergens');
    }
};
