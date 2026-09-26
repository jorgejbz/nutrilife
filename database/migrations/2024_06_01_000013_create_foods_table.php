<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Si la BD se importó desde proyecto_v3.sql, la tabla ya existe.
        if (Schema::hasTable('foods')) {
            return;
        }

        Schema::create('foods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('food_group_id')->constrained('food_groups');
            $table->string('name');
            $table->decimal('portion_grams', 6, 2)
                ->comment('Gramaje de 1 equivalente, ej. 30g de tortilla');
            $table->string('portion_description')->nullable()
                ->comment('"1 pieza chica", "1/2 taza"');
            $table->decimal('kcal', 7, 2);
            $table->decimal('protein_g', 6, 2);
            $table->decimal('lipids_g', 6, 2);
            $table->decimal('carbs_g', 6, 2);
            $table->decimal('fiber_g', 6, 2)->nullable();
            $table->decimal('sodium_mg', 7, 2)->nullable()
                ->comment('Clave para pacientes con hipertensión');
            $table->unsignedSmallInteger('glycemic_index')->nullable()
                ->comment('Útil para pacientes con diabetes');
            $table->string('source', 100)->default('INCAP-SMAE');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foods');
    }
};
