<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | PERMITIR ALIMENTOS MANUALES
        |--------------------------------------------------------------------------
        |
        | food_id seguirá apuntando a foods.
        |
        | Pero ahora podrá ser NULL cuando el nutriólogo agregue
        | un alimento personalizado únicamente dentro de una dieta.
        |
        */

        DB::statement("
            ALTER TABLE `diet_plan_items`
            MODIFY `food_id` BIGINT UNSIGNED NULL
        ");


        /*
        |--------------------------------------------------------------------------
        | CAMPOS DEL ALIMENTO PERSONALIZADO
        |--------------------------------------------------------------------------
        */

        Schema::table('diet_plan_items', function (Blueprint $table) {

            $table->string('custom_name')
                ->nullable()
                ->after('food_id');


            $table->decimal('custom_kcal', 8, 2)
                ->nullable()
                ->after('custom_name');


            $table->decimal('custom_protein_g', 8, 2)
                ->nullable()
                ->after('custom_kcal');


            $table->decimal('custom_carbs_g', 8, 2)
                ->nullable()
                ->after('custom_protein_g');


            $table->decimal('custom_lipids_g', 8, 2)
                ->nullable()
                ->after('custom_carbs_g');


            $table->string('custom_portion_description')
                ->nullable()
                ->after('custom_lipids_g');

        });
    }


    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | ELIMINAR CAMPOS PERSONALIZADOS
        |--------------------------------------------------------------------------
        */

        Schema::table('diet_plan_items', function (Blueprint $table) {

            $table->dropColumn([
                'custom_name',
                'custom_kcal',
                'custom_protein_g',
                'custom_carbs_g',
                'custom_lipids_g',
                'custom_portion_description',
            ]);

        });


        /*
        |--------------------------------------------------------------------------
        | VOLVER food_id A NOT NULL
        |--------------------------------------------------------------------------
        |
        | Si algún día hacemos rollback, eliminamos primero los items
        | personalizados porque estos tienen food_id = NULL.
        |
        */

        DB::table('diet_plan_items')
            ->whereNull('food_id')
            ->delete();


        DB::statement("
            ALTER TABLE `diet_plan_items`
            MODIFY `food_id` BIGINT UNSIGNED NOT NULL
        ");
    }
};