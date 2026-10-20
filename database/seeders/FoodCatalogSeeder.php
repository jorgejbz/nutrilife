<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FoodCatalogSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | GRUPOS DE ALIMENTOS
        |--------------------------------------------------------------------------
        */

        $groups = [
            'Cereales',
            'Frutas',
            'Verduras',
            'Proteínas',
            'Lácteos',
            'Grasas y semillas',
        ];

        foreach ($groups as $group) {

            DB::table('food_groups')->updateOrInsert(
                [
                    'name' => $group,
                ],
                [
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | OBTENER IDS
        |--------------------------------------------------------------------------
        */

        $cereales = DB::table('food_groups')
            ->where('name', 'Cereales')
            ->value('id');

        $frutas = DB::table('food_groups')
            ->where('name', 'Frutas')
            ->value('id');

        $verduras = DB::table('food_groups')
            ->where('name', 'Verduras')
            ->value('id');

        $proteinas = DB::table('food_groups')
            ->where('name', 'Proteínas')
            ->value('id');

        $lacteos = DB::table('food_groups')
            ->where('name', 'Lácteos')
            ->value('id');

        $grasas = DB::table('food_groups')
            ->where('name', 'Grasas y semillas')
            ->value('id');


        /*
        |--------------------------------------------------------------------------
        | ALIMENTOS DE PRUEBA
        |--------------------------------------------------------------------------
        */

        $foods = [

            [
                'food_group_id' => $cereales,
                'name' => 'Avena',
                'portion_grams' => 100,
                'portion_description' => '100 g',
                'kcal' => 389,
                'protein_g' => 16.9,
                'lipids_g' => 6.9,
                'carbs_g' => 66.3,
            ],

            [
                'food_group_id' => $cereales,
                'name' => 'Arroz blanco cocido',
                'portion_grams' => 100,
                'portion_description' => '100 g',
                'kcal' => 130,
                'protein_g' => 2.7,
                'lipids_g' => 0.3,
                'carbs_g' => 28.2,
            ],

            [
                'food_group_id' => $frutas,
                'name' => 'Plátano',
                'portion_grams' => 100,
                'portion_description' => '100 g',
                'kcal' => 89,
                'protein_g' => 1.1,
                'lipids_g' => 0.3,
                'carbs_g' => 22.8,
            ],

            [
                'food_group_id' => $frutas,
                'name' => 'Manzana',
                'portion_grams' => 100,
                'portion_description' => '100 g',
                'kcal' => 52,
                'protein_g' => 0.3,
                'lipids_g' => 0.2,
                'carbs_g' => 13.8,
            ],

            [
                'food_group_id' => $proteinas,
                'name' => 'Pechuga de pollo',
                'portion_grams' => 100,
                'portion_description' => '100 g',
                'kcal' => 165,
                'protein_g' => 31,
                'lipids_g' => 3.6,
                'carbs_g' => 0,
            ],

            [
             'food_group_id' => $proteinas,
            'name' => 'Huevo entero',

            // 1 huevo mediano ≈ 50 g
            'portion_grams' => 50,

            // Esto es lo que verá el nutriólogo
            'portion_description' => '1 pieza',

            // Nutrientes correspondientes a 1 pieza / 50 g
            'kcal' => 71.5,
            'protein_g' => 6.3,
            'lipids_g' => 4.75,
            'carbs_g' => 0.35,
],

            [
                'food_group_id' => $lacteos,
                'name' => 'Yogurt griego natural',
                'portion_grams' => 100,
                'portion_description' => '100 g',
                'kcal' => 97,
                'protein_g' => 9,
                'lipids_g' => 5,
                'carbs_g' => 3.9,
            ],

            [
                'food_group_id' => $grasas,
                'name' => 'Almendras',
                'portion_grams' => 100,
                'portion_description' => '100 g',
                'kcal' => 579,
                'protein_g' => 21.2,
                'lipids_g' => 49.9,
                'carbs_g' => 21.6,
            ],

            [
                'food_group_id' => $grasas,
                'name' => 'Aguacate',
                'portion_grams' => 100,
                'portion_description' => '100 g',
                'kcal' => 160,
                'protein_g' => 2,
                'lipids_g' => 14.7,
                'carbs_g' => 8.5,
            ],

            [
                'food_group_id' => $verduras,
                'name' => 'Brócoli',
                'portion_grams' => 100,
                'portion_description' => '100 g',
                'kcal' => 34,
                'protein_g' => 2.8,
                'lipids_g' => 0.4,
                'carbs_g' => 6.6,
            ],

        ];


        foreach ($foods as $food) {

            DB::table('foods')->updateOrInsert(
                [
                    'name' => $food['name'],
                ],
                [
                    'food_group_id' =>
                        $food['food_group_id'],

                    'portion_grams' =>
                        $food['portion_grams'],

                    'portion_description' =>
                        $food['portion_description'],

                    'kcal' =>
                        $food['kcal'],

                    'protein_g' =>
                        $food['protein_g'],

                    'lipids_g' =>
                        $food['lipids_g'],

                    'carbs_g' =>
                        $food['carbs_g'],

                    'fiber_g' => null,

                    'sodium_mg' => null,

                    'glycemic_index' => null,

                    'source' => 'Prueba NutriLife',

                    'active' => true,

                    'updated_at' => now(),

                    'created_at' => now(),
                ]
            );
        }
    }
}