<?php

namespace Database\Seeders;

use App\Models\Allergen;
use App\Models\Food;
use App\Models\FoodGroup;
use App\Models\Pathology;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo INCMNSZ 2015 (database/data/catalogo_incmnsz_2015.json),
 * extraído de proyecto_con_catalogos.sql.
 *
 *   php artisan db:seed --class=IncmnszCatalogSeeder
 *
 * Solo AGREGA, se puede ejecutar varias veces y no reemplaza nada:
 *  - Alérgenos y patologías: los que falten (por nombre).
 *  - Grupos de alimentos: los que falten (por nombre; "Frutas", "Verduras"
 *    y "Leche y derivados" se reutilizan).
 *  - Alimentos: los 1,513 del INCMNSZ con valores por 100 g. Si ya existe
 *    un alimento con el mismo nombre (p. ej. "Manzana" del catálogo SMAE)
 *    se conserva el existente: los planes y platillos lo usan por ID.
 *  - Alérgenos de cada alimento: solo para los alimentos que agrega.
 *
 * El dump original NO se importa completo: sus IDs de `foods` son otros
 * (el 1 es "Arroz, atole", no "Manzana") y traía diet_plan_items vacío.
 */
class IncmnszCatalogSeeder extends Seeder
{
    public const DATA_FILE = 'data/catalogo_incmnsz_2015.json';

    public function run(): void
    {
        $data = json_decode(file_get_contents(database_path(self::DATA_FILE)), true, flags: JSON_THROW_ON_ERROR);

        $stats = DB::transaction(function () use ($data) {
            $stats = ['alérgenos' => 0, 'patologías' => 0, 'grupos' => 0, 'alimentos' => 0, 'ya existían' => 0, 'relaciones alimento-alérgeno' => 0];

            foreach ($data['allergens'] as $name) {
                $stats['alérgenos'] += Allergen::firstOrCreate(['name' => $name])->wasRecentlyCreated ? 1 : 0;
            }

            foreach ($data['pathologies'] as $pathology) {
                $stats['patologías'] += Pathology::firstOrCreate(['name' => $pathology['name']], ['description' => $pathology['description']])->wasRecentlyCreated ? 1 : 0;
            }

            $groups = [];
            foreach ($data['food_groups'] as $name) {
                $group = FoodGroup::firstOrCreate(['name' => $name]);
                $groups[$name] = $group->id;
                $stats['grupos'] += $group->wasRecentlyCreated ? 1 : 0;
            }

            $allergens = Allergen::pluck('id', 'name');
            $existing = Food::pluck('id', 'name');
            $now = now();

            foreach ($data['foods'] as $food) {
                if ($existing->has($food['name'])) {
                    $stats['ya existían']++;

                    continue;
                }

                $id = DB::table('foods')->insertGetId([
                    'food_group_id' => $groups[$food['group']],
                    'name' => $food['name'],
                    'portion_grams' => $food['portion_grams'],
                    'portion_description' => $food['portion_description'],
                    'kcal' => $food['kcal'],
                    'protein_g' => $food['protein_g'],
                    'lipids_g' => $food['lipids_g'],
                    'carbs_g' => $food['carbs_g'],
                    'fiber_g' => $food['fiber_g'],
                    'sodium_mg' => $food['sodium_mg'],
                    'glycemic_index' => $food['glycemic_index'],
                    'source' => $food['source'],
                    'active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $existing[$food['name']] = $id;
                $stats['alimentos']++;

                $rows = collect($food['allergens'])
                    ->map(fn ($name) => $allergens[$name] ?? null)
                    ->filter()->unique()
                    ->map(fn ($allergenId) => ['food_id' => $id, 'allergen_id' => $allergenId])
                    ->values()->all();

                if ($rows) {
                    DB::table('food_allergens')->insert($rows);
                    $stats['relaciones alimento-alérgeno'] += count($rows);
                }
            }

            return $stats;
        });

        $this->command?->info('INCMNSZ 2015 agregado: '.collect($stats)->map(fn ($count, $label) => "$count $label")->join(', ').'.');
    }
}
