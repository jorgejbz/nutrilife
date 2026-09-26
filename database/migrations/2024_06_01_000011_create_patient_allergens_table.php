<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Si la BD se importó desde proyecto_v3.sql, la tabla ya existe.
        if (Schema::hasTable('patient_allergens')) {
            return;
        }

        Schema::create('patient_allergens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('allergen_id')->constrained('allergens');
            $table->enum('severity', ['leve', 'moderada', 'severa'])->nullable();
            $table->timestamps();

            $table->unique(['patient_id', 'allergen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_allergens');
    }
};
