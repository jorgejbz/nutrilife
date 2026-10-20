<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro profesional y activación de cuentas (cambio SOLO aditivo).
 *
 * - professional_license / degree_number: cédula profesional y número de
 *   título que captura el registro. Guardarlos NO significa que estén
 *   verificados.
 * - professional_verified_at: verificación profesional REAL (hoy nadie la
 *   llena: queda lista para un servicio externo o una revisión manual).
 * - account_status: 'pendiente' | 'activa'. Las cuentas que ya existen
 *   quedan 'activa' por el valor por defecto, así que nadie se bloquea.
 * - activates_at: cuándo se habilita una cuenta pendiente
 *   (config nutrilife.account_activation.delay_minutes).
 * - activated_at: cuándo se habilitó.
 *
 * Equivalente en SQL: database/sql/actualizacion_v3_1.sql
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'professional_license')) {
                $table->string('professional_license', 20)->nullable()->unique()->after('access_code');
            }

            if (! Schema::hasColumn('users', 'degree_number')) {
                $table->string('degree_number', 50)->nullable()->after('professional_license');
            }

            if (! Schema::hasColumn('users', 'professional_verified_at')) {
                $table->timestamp('professional_verified_at')->nullable()->after('degree_number');
            }

            if (! Schema::hasColumn('users', 'account_status')) {
                $table->string('account_status', 20)->default('activa')->after('professional_verified_at');
            }

            if (! Schema::hasColumn('users', 'activates_at')) {
                $table->timestamp('activates_at')->nullable()->after('account_status');
            }

            if (! Schema::hasColumn('users', 'activated_at')) {
                $table->timestamp('activated_at')->nullable()->after('activates_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'professional_license')) {
                $table->dropUnique(['professional_license']);
            }

            $table->dropColumn(array_values(array_filter(
                ['professional_license', 'degree_number', 'professional_verified_at', 'account_status', 'activates_at', 'activated_at'],
                fn (string $column) => Schema::hasColumn('users', $column)
            )));
        });
    }
};
