<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crédito de la foto de un platillo (actualización 3.3).
 *
 * Las fotos de los platillos del sistema vienen de Wikimedia Commons con
 * licencias que piden atribución (CC BY / CC BY-SA): «Autor · Licencia».
 * Aditiva y opcional (NULL = sin crédito, p. ej. fotos propias).
 *
 * Equivalente en SQL: database/sql/actualizacion_v3_3.sql
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('diet_plan_dishes', 'image_credit')) {
            Schema::table('diet_plan_dishes', function (Blueprint $table) {
                $table->string('image_credit', 255)->nullable()->after('image_url');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('diet_plan_dishes', 'image_credit')) {
            Schema::table('diet_plan_dishes', function (Blueprint $table) {
                $table->dropColumn('image_credit');
            });
        }
    }
};
