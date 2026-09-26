<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Si la BD se importó desde proyecto_v3.sql, la tabla ya existe.
        if (Schema::hasTable('diet_plans')) {
            return;
        }

        Schema::create('diet_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('user_id')->constrained('users')
                ->comment('Nutriólogo que creó el plan');
            $table->string('name');
            $table->decimal('target_kcal', 7, 2);
            $table->decimal('target_protein_g', 6, 2);
            $table->decimal('target_lipids_g', 6, 2);
            $table->decimal('target_carbs_g', 6, 2);
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diet_plans');
    }
};
