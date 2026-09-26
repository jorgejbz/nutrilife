<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Si la BD se importó desde proyecto_v3.sql, la tabla ya existe.
        if (Schema::hasTable('subscriptions')) {
            return;
        }

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()
                ->comment('El nutriólogo dueño de la suscripción');
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans');
            $table->enum('status', ['en_prueba', 'activa', 'vencida', 'cancelada'])->default('en_prueba');
            $table->date('starts_at');
            $table->date('current_period_ends_at');
            $table->date('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
