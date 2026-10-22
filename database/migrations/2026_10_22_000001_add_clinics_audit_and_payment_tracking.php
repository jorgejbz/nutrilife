<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consultorios con varios nutriólogos, administrador global y trazabilidad
 * de pagos (actualización 4). Cambio SOLO aditivo: no borra ni modifica
 * registros existentes.
 *
 * - users.clinic_id: el subperfil de nutriólogo apunta a la cuenta principal
 *   de su consultorio (otro registro de `users`). NULL = cuenta individual o
 *   cuenta principal de la clínica. Los pacientes NO cambian: siguen ligados
 *   a su nutriólogo responsable (patients.user_id) y la clínica se obtiene a
 *   través de él (sin columnas duplicadas).
 * - payments / subscription_payments.status_changed_by / _at: quién cambió
 *   el estado del pago y cuándo.
 * - audit_logs: bitácora de acciones sensibles (administrador, clínica,
 *   cambios de estado de pagos).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'clinic_id')) {
                $table->foreignId('clinic_id')->nullable()->after('role_id')
                    ->comment('Cuenta principal del consultorio al que pertenece el subperfil')
                    ->constrained('users')->restrictOnDelete();
            }
        });

        foreach (['payments', 'subscription_payments'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'status_changed_by')) {
                    $table->foreignId('status_changed_by')->nullable()->after('status')
                        ->comment('Usuario que cambió el estado del pago')
                        ->constrained('users')->nullOnDelete();
                }

                if (! Schema::hasColumn($tableName, 'status_changed_at')) {
                    $table->timestamp('status_changed_at')->nullable()->after('status_changed_by');
                }
            });
        }

        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()
                    ->comment('Quién realizó la acción')
                    ->constrained('users')->nullOnDelete();
                $table->string('action', 60);
                $table->string('subject_type', 60)->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->json('details')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index(['subject_type', 'subject_id']);
                $table->index('action');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');

        foreach (['payments', 'subscription_payments'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'status_changed_by')) {
                    $table->dropConstrainedForeignId('status_changed_by');
                }

                if (Schema::hasColumn($tableName, 'status_changed_at')) {
                    $table->dropColumn('status_changed_at');
                }
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'clinic_id')) {
                $table->dropConstrainedForeignId('clinic_id');
            }
        });
    }
};
