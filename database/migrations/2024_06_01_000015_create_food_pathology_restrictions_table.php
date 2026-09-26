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
        if (Schema::hasTable('food_pathology_restrictions')) {
            return;
        }

        Schema::create('food_pathology_restrictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pathology_id')->constrained('pathologies')->cascadeOnDelete();
            $table->foreignId('food_id')->nullable()->constrained('foods')->cascadeOnDelete();
            $table->foreignId('food_group_id')->nullable()->constrained('food_groups')->cascadeOnDelete();
            $table->enum('restriction_type', ['prohibido', 'limitado']);
            $table->decimal('max_portions_per_day', 4, 1)->nullable()
                ->comment('Solo aplica si restriction_type = limitado');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Laravel 13 no ofrece Blueprint::check(); el CHECK de proyecto_v3.sql
        // se agrega con SQL nativo (MySQL 8.0.16+ / MariaDB 10.2+ lo aplican).
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement(
                'ALTER TABLE `food_pathology_restrictions`
                 ADD CONSTRAINT `fpr_food_or_group_check` CHECK (
                    (`food_id` IS NOT NULL AND `food_group_id` IS NULL) OR
                    (`food_id` IS NULL AND `food_group_id` IS NOT NULL)
                 )'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('food_pathology_restrictions');
    }
};
