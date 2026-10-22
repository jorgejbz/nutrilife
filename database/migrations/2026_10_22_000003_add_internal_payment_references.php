<?php

use App\Services\PaymentReferenceService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * REFERENCIA INTERNA DE NUTRILIFE PARA TODOS LOS PAGOS (incremental).
 *
 * - payments.internal_reference y subscription_payments.internal_reference:
 *   la genera el servidor (App\Services\PaymentReferenceService) con el
 *   prefijo del método de pago, p. ej. TRA-20261022-000123. Única por tabla
 *   (índice UNIQUE) y entre tablas (un solo contador para toda la plataforma).
 * - La columna `reference` que ya existía NO se toca: sigue siendo la
 *   referencia externa (clave de rastreo SPEI, folio de la terminal, id de
 *   la pasarela) o el código de origen que tenían los cobros de planes.
 * - reference_counters: contador persistente (no en memoria) que se lee con
 *   bloqueo de fila para que dos pagos simultáneos nunca reciban el mismo número.
 *
 * Al final asigna referencia a los pagos históricos que no tienen
 * (idempotente: `php artisan nutrilife:payment-references` hace lo mismo).
 * No modifica montos, fechas, estados ni relaciones.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reference_counters')) {
            Schema::create('reference_counters', function (Blueprint $table) {
                $table->string('name', 50)->primary();
                $table->unsignedBigInteger('value')->default(0);
                $table->timestamps();
            });
        }

        foreach (['payments', 'subscription_payments'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'internal_reference')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->string('internal_reference', 40)->nullable()->after('reference');
                    $table->unique('internal_reference', $tableName.'_internal_reference_unique');
                });
            }
        }

        PaymentReferenceService::backfill();
    }

    public function down(): void
    {
        foreach (['payments', 'subscription_payments'] as $tableName) {
            if (Schema::hasColumn($tableName, 'internal_reference')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropUnique($tableName.'_internal_reference_unique');
                    $table->dropColumn('internal_reference');
                });
            }
        }

        Schema::dropIfExists('reference_counters');
    }
};
