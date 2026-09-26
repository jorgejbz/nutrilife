<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Si la BD se importó desde proyecto_v3.sql, la tabla ya existe.
        if (Schema::hasTable('consultations')) {
            return;
        }

        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments');
            $table->dateTime('consultation_at');
            $table->string('reason')->nullable();
            $table->text('observations')->nullable();
            $table->text('recommendations')->nullable();
            $table->date('next_visit')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
