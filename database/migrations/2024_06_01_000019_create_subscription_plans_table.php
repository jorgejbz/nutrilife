<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Si la BD se importó desde proyecto_v3.sql, la tabla ya existe.
        if (Schema::hasTable('subscription_plans')) {
            return;
        }

        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)
                ->comment('Básico, Pro, Clínica...');
            $table->decimal('price', 8, 2);
            $table->enum('billing_period', ['mensual', 'anual'])->default('mensual');
            $table->unsignedInteger('max_patients')->nullable()
                ->comment('NULL = sin límite');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
