<?php

namespace Database\Seeders;

use App\Models\Allergen;
use App\Models\Food;
use App\Models\FoodGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo inicial de alimentos (tablas `food_groups`, `foods` y
 * `food_allergens`) para armar planes alimenticios.
 *
 *   php artisan db:seed --class=FoodSeeder
 *
 * CONVENCIÓN DE LA BD (migración create_foods_table): los nutrientes de
 * `foods` son POR PORCIÓN, y `portion_grams` es el gramaje de esa porción
 * ("Gramaje de 1 equivalente, ej. 30 g de tortilla"). Por eso aquí cada
 * alimento se escribe con su composición por 100 g y su porción casera; el
 * seeder calcula los valores de la porción (valor_100g × gramos / 100).
 * El constructor de planes vuelve a escalar según los gramos que se elijan.
 *
 * Valores APROXIMADOS de tablas de composición de referencia (USDA
 * FoodData Central; queso panela con el equivalente del SMAE), para el estado
 * indicado en el nombre (crudo, cocido, escurrido). Los cocidos son sin sal
 * añadida. glycemic_index solo se llena en alimentos con un valor de
 * referencia conocido (tablas internacionales de IG, glucosa = 100); en el
 * resto queda vacío. Revisa y ajusta los valores antes de uso clínico.
 *
 * Seguro de ejecutar varias veces:
 *  - La clave estable es el nombre del alimento (la tabla no tiene otra).
 *  - Si un alimento con ese nombre ya existe y lo cargó este seeder (o el
 *    catálogo de prueba anterior), se actualizan sus valores.
 *  - Si existe pero lo cargó alguien más (otro `source`), NO se toca.
 *  - Nunca borra alimentos.
 */
class FoodSeeder extends Seeder
{
    public const SOURCE = 'NutriLife · aprox. USDA FDC';

    /** Catálogo de prueba de la versión 3.1 (DemoFoodCatalogSeeder): se reemplaza. */
    public const LEGACY_SOURCE = 'Prueba (promedio SMAE)';

    /** Nombre en el catálogo de prueba anterior → nombre nuevo */
    private const LEGACY_NAMES = [
        'Arroz cocido' => 'Arroz blanco (cocido)',
        'Avena en hojuelas' => 'Avena en hojuelas (cruda)',
        'Brócoli cocido' => 'Brócoli (cocido)',
        'Calabacita cocida' => 'Calabacita (cocida)',
        'Nopales cocidos' => 'Nopales (cocidos)',
        'Zanahoria' => 'Zanahoria (cruda)',
        'Lechuga' => 'Lechuga romana',
        'Papa cocida' => 'Papa sin cáscara (cocida)',
        'Pasta cocida' => 'Pasta (cocida)',
        'Frijol cocido' => 'Frijol negro (cocido)',
        'Lenteja cocida' => 'Lenteja (cocida)',
        'Pechuga de pollo sin piel' => 'Pechuga de pollo sin piel (cocida)',
        'Atún en agua' => 'Atún en agua (escurrido)',
        'Pescado blanco' => 'Pescado blanco, tilapia (cocido)',
        'Claras de huevo' => 'Claras de huevo (crudas)',
        'Huevo entero' => 'Huevo entero (crudo)',
        'Camarón cocido' => 'Camarón (cocido)',
        'Aguacate' => 'Aguacate Hass',
        'Crema de cacahuate' => 'Crema de cacahuate natural',
        'Miel' => 'Miel de abeja',
    ];

    /** Grupos de alimentos (food_groups.name es UNIQUE) */
    private const GROUPS = [
        'fru' => 'Frutas',
        'ver' => 'Verduras',
        'cer' => 'Cereales y tubérculos',
        'leg' => 'Leguminosas',
        'aoa' => 'Alimentos de origen animal',
        'lac' => 'Leche y derivados',
        'gra' => 'Grasas, aceites y semillas',
        'azu' => 'Azúcares',
    ];

    /** Alérgenos (nombres de ClinicalCatalogSeeder) */
    private const A_GLUTEN = 'Gluten (trigo, cebada, centeno)';
    private const A_MILK = 'Leche (proteína de la leche)';
    private const A_LACTOSE = 'Lactosa (intolerancia)';
    private const A_EGG = 'Huevo';
    private const A_PEANUT = 'Cacahuate';
    private const A_NUTS = 'Nueces y frutos de cáscara';
    private const A_FISH = 'Pescado';
    private const A_SHELLFISH = 'Mariscos (crustáceos y moluscos)';

    /**
     * [grupo, nombre (con estado), gramos de la porción, porción casera,
     *  POR 100 g: kcal, proteína, lípidos, hidratos, fibra, sodio mg,
     *  índice glucémico|null, alérgenos[]]
     */
    private static function catalog(): array
    {
        $milk = [self::A_MILK, self::A_LACTOSE];

        return [
            // ---------------- Frutas (crudas) ----------------
            ['fru', 'Manzana', 106, '1 pieza chica', 52, 0.3, 0.2, 13.8, 2.4, 1, 36, []],
            ['fru', 'Plátano', 54, '1/2 pieza', 89, 1.1, 0.3, 22.8, 2.6, 1, 51, []],
            ['fru', 'Naranja', 130, '1 pieza mediana', 47, 0.9, 0.1, 11.8, 2.4, 0, 43, []],
            ['fru', 'Papaya', 140, '1 taza en cubos', 43, 0.5, 0.3, 10.8, 1.7, 8, 60, []],
            ['fru', 'Fresas', 150, '1 taza', 32, 0.7, 0.3, 7.7, 2.0, 1, 40, []],
            ['fru', 'Mango', 100, '1/2 pieza', 60, 0.8, 0.4, 15.0, 1.6, 1, 51, []],
            ['fru', 'Piña', 125, '3/4 de taza en cubos', 50, 0.5, 0.1, 13.1, 1.4, 1, 59, []],
            ['fru', 'Sandía', 150, '1 taza en cubos', 30, 0.6, 0.2, 7.6, 0.4, 1, 72, []],
            ['fru', 'Melón', 160, '1 taza en cubos', 34, 0.8, 0.2, 8.2, 0.9, 16, 65, []],
            ['fru', 'Uvas', 90, '18 piezas', 69, 0.7, 0.2, 18.1, 0.9, 2, 59, []],
            ['fru', 'Pera', 90, '1/2 pieza', 57, 0.4, 0.1, 15.2, 3.1, 1, 38, []],
            ['fru', 'Guayaba', 85, '2 piezas chicas', 68, 2.6, 1.0, 14.3, 5.4, 2, null, []],
            ['fru', 'Mandarina', 100, '1 pieza', 53, 0.8, 0.3, 13.3, 1.8, 2, null, []],
            ['fru', 'Kiwi', 75, '1 pieza', 61, 1.1, 0.5, 14.7, 3.0, 3, 52, []],
            ['fru', 'Arándanos', 110, '3/4 de taza', 57, 0.7, 0.3, 14.5, 2.4, 1, 53, []],
            ['fru', 'Durazno', 150, '1 pieza mediana', 39, 0.9, 0.3, 9.5, 1.5, 0, 42, []],
            ['fru', 'Toronja', 125, '1/2 pieza', 42, 0.8, 0.1, 10.7, 1.6, 0, 25, []],
            ['fru', 'Ciruela', 130, '2 piezas', 46, 0.7, 0.3, 11.4, 1.4, 0, null, []],

            // ---------------- Verduras ----------------
            ['ver', 'Brócoli (cocido)', 78, '1/2 taza', 35, 2.4, 0.4, 7.2, 3.3, 41, null, []],
            ['ver', 'Zanahoria (cruda)', 64, '1/2 taza picada', 41, 0.9, 0.2, 9.6, 2.8, 69, null, []],
            ['ver', 'Espinaca (cruda)', 60, '2 tazas', 23, 2.9, 0.4, 3.6, 2.2, 79, null, []],
            ['ver', 'Jitomate', 123, '1 pieza', 18, 0.9, 0.2, 3.9, 1.2, 5, null, []],
            ['ver', 'Pepino con cáscara', 104, '1 taza en rodajas', 15, 0.7, 0.1, 3.6, 0.5, 2, null, []],
            ['ver', 'Lechuga romana', 94, '2 tazas', 17, 1.2, 0.3, 3.3, 2.1, 8, null, []],
            ['ver', 'Calabacita (cocida)', 90, '1/2 taza', 15, 1.1, 0.4, 2.7, 1.0, 3, null, []],
            ['ver', 'Nopales (cocidos)', 150, '1 taza', 15, 1.4, 0.1, 3.3, 2.0, 1, null, []],
            ['ver', 'Chayote (cocido)', 80, '1/2 taza', 24, 0.6, 0.5, 5.1, 2.8, 1, null, []],
            ['ver', 'Cebolla blanca (cruda)', 40, '1/4 de taza picada', 40, 1.1, 0.1, 9.3, 1.7, 4, null, []],
            ['ver', 'Pimiento morrón rojo (crudo)', 75, '1/2 taza', 31, 1.0, 0.3, 6.0, 2.1, 4, null, []],
            ['ver', 'Champiñones (crudos)', 70, '1 taza', 22, 3.1, 0.3, 3.3, 1.0, 5, null, []],
            ['ver', 'Coliflor (cocida)', 62, '1/2 taza', 23, 1.8, 0.5, 4.1, 2.3, 15, null, []],
            ['ver', 'Ejotes (cocidos)', 62, '1/2 taza', 35, 1.9, 0.3, 7.9, 3.2, 1, null, []],
            ['ver', 'Chile jalapeño (crudo)', 14, '1 pieza', 29, 0.9, 0.4, 6.5, 2.8, 3, null, []],
            ['ver', 'Apio (crudo)', 40, '1 tallo mediano', 14, 0.7, 0.2, 3.0, 1.6, 80, null, []],
            ['ver', 'Betabel (cocido)', 85, '1/2 taza', 44, 1.7, 0.2, 10.0, 2.0, 77, null, []],
            ['ver', 'Col (cruda)', 89, '1 taza picada', 25, 1.3, 0.1, 5.8, 2.5, 18, null, []],
            ['ver', 'Acelga (cocida)', 88, '1/2 taza', 20, 1.9, 0.1, 4.1, 2.1, 179, null, []],

            // ---------------- Cereales y tubérculos ----------------
            ['cer', 'Tortilla de maíz', 30, '1 pieza', 218, 5.7, 2.9, 44.6, 6.3, 45, null, []],
            ['cer', 'Tortilla de harina de trigo', 30, '1 pieza chica', 304, 8.2, 8.0, 49.4, 3.5, 640, null, [self::A_GLUTEN]],
            ['cer', 'Arroz blanco (cocido)', 80, '1/2 taza', 130, 2.7, 0.3, 28.2, 0.4, 1, 73, []],
            ['cer', 'Arroz blanco (crudo)', 20, '2 cucharadas', 365, 7.1, 0.7, 80.0, 1.3, 5, null, []],
            ['cer', 'Arroz integral (cocido)', 98, '1/2 taza', 123, 2.7, 1.0, 25.6, 1.6, 4, 68, []],
            ['cer', 'Avena en hojuelas (cruda)', 27, '1/3 de taza', 379, 13.2, 6.5, 67.7, 10.1, 6, 55, [self::A_GLUTEN]],
            ['cer', 'Pan integral', 32, '1 rebanada', 252, 12.4, 3.5, 42.7, 6.0, 450, 74, [self::A_GLUTEN]],
            ['cer', 'Pan blanco de caja', 25, '1 rebanada', 266, 7.6, 3.3, 49.4, 2.7, 490, 75, [self::A_GLUTEN]],
            ['cer', 'Pasta (cocida)', 70, '1/2 taza', 158, 5.8, 0.9, 30.9, 1.8, 1, 49, [self::A_GLUTEN]],
            ['cer', 'Papa sin cáscara (cocida)', 85, '1/2 pieza mediana', 87, 1.9, 0.1, 20.1, 1.8, 5, 78, []],
            ['cer', 'Camote (cocido)', 100, '1/2 taza', 76, 1.4, 0.1, 17.7, 2.5, 27, 63, []],
            ['cer', 'Elote amarillo (cocido)', 82, '1/2 taza en granos', 96, 3.4, 1.5, 21.0, 2.4, 1, 52, []],
            ['cer', 'Quinoa (cocida)', 92, '1/2 taza', 120, 4.4, 1.9, 21.3, 2.8, 7, 53, []],
            ['cer', 'Palomitas naturales sin aceite', 20, '2 1/2 tazas', 387, 12.9, 4.5, 77.8, 14.5, 8, null, []],

            // ---------------- Leguminosas (cocidas, sin sal) ----------------
            ['leg', 'Frijol negro (cocido)', 86, '1/2 taza', 132, 8.9, 0.5, 23.7, 8.7, 1, 30, []],
            ['leg', 'Frijol pinto (cocido)', 86, '1/2 taza', 143, 9.0, 0.7, 26.2, 9.0, 1, 39, []],
            ['leg', 'Lenteja (cocida)', 99, '1/2 taza', 116, 9.0, 0.4, 20.1, 7.9, 2, 32, []],
            ['leg', 'Garbanzo (cocido)', 82, '1/2 taza', 164, 8.9, 2.6, 27.4, 7.6, 7, 28, []],
            ['leg', 'Haba seca (cocida)', 85, '1/2 taza', 110, 7.6, 0.4, 19.7, 5.4, 5, null, []],
            ['leg', 'Alubia blanca (cocida)', 90, '1/2 taza', 139, 9.7, 0.4, 25.1, 6.3, 6, null, []],

            // ---------------- Alimentos de origen animal ----------------
            ['aoa', 'Pechuga de pollo sin piel (cocida)', 30, '30 g', 165, 31.0, 3.6, 0, 0, 74, null, []],
            ['aoa', 'Muslo de pollo sin piel (cocido)', 30, '30 g', 209, 26.0, 10.9, 0, 0, 95, null, []],
            ['aoa', 'Huevo entero (crudo)', 50, '1 pieza', 143, 12.6, 9.5, 0.7, 0, 142, null, [self::A_EGG]],
            ['aoa', 'Claras de huevo (crudas)', 66, '2 piezas', 52, 10.9, 0.2, 0.7, 0, 166, null, [self::A_EGG]],
            ['aoa', 'Atún en agua (escurrido)', 30, '30 g', 116, 25.5, 0.8, 0, 0, 338, null, [self::A_FISH]],
            ['aoa', 'Pescado blanco, tilapia (cocido)', 30, '30 g', 128, 26.2, 2.7, 0, 0, 56, null, [self::A_FISH]],
            ['aoa', 'Salmón (cocido)', 30, '30 g', 206, 22.1, 12.4, 0, 0, 61, null, [self::A_FISH]],
            ['aoa', 'Sardina en aceite (escurrida)', 30, '30 g', 208, 24.6, 11.5, 0, 0, 307, null, [self::A_FISH]],
            ['aoa', 'Camarón (cocido)', 30, '30 g (≈ 4 piezas medianas)', 99, 24.0, 0.3, 0.2, 0, 111, null, [self::A_SHELLFISH]],
            ['aoa', 'Bistec de res magro (cocido)', 30, '30 g', 183, 30.6, 6.0, 0, 0, 66, null, []],
            ['aoa', 'Carne molida de res 90 % magra (cocida)', 30, '30 g', 217, 26.1, 11.7, 0, 0, 76, null, []],
            ['aoa', 'Lomo de cerdo (cocido)', 30, '30 g', 143, 26.2, 3.5, 0, 0, 57, null, []],

            // ---------------- Leche y derivados ----------------
            ['lac', 'Leche descremada', 244, '1 taza (240 ml)', 34, 3.4, 0.1, 5.0, 0, 42, 32, $milk],
            ['lac', 'Leche semidescremada', 244, '1 taza (240 ml)', 50, 3.3, 2.0, 4.8, 0, 47, null, $milk],
            ['lac', 'Leche entera', 244, '1 taza (240 ml)', 61, 3.2, 3.3, 4.8, 0, 43, 39, $milk],
            ['lac', 'Yogur natural descremado', 245, '1 taza', 56, 5.7, 0.2, 7.7, 0, 77, null, $milk],
            ['lac', 'Yogur griego natural sin grasa', 150, '1 envase', 59, 10.2, 0.4, 3.6, 0, 36, null, $milk],
            ['lac', 'Queso panela', 40, '40 g', 137.5, 17.5, 7.5, 0, 0, null, null, $milk],
            ['lac', 'Queso cottage bajo en grasa', 113, '1/2 taza', 81, 10.5, 2.3, 4.3, 0, 308, null, $milk],
            ['lac', 'Queso mozzarella semidescremado', 30, '30 g', 254, 24.3, 15.9, 2.8, 0, 619, null, $milk],

            // ---------------- Grasas, aceites y semillas ----------------
            ['gra', 'Aguacate Hass', 30, '1/3 de pieza', 167, 2.0, 15.4, 8.6, 6.8, 8, null, []],
            ['gra', 'Almendras', 12, '10 piezas', 579, 21.2, 49.9, 21.6, 12.5, 1, null, [self::A_NUTS]],
            ['gra', 'Nueces', 9, '3 piezas', 654, 15.2, 65.2, 13.7, 6.7, 2, null, [self::A_NUTS]],
            ['gra', 'Cacahuates tostados sin sal', 10, '14 piezas', 587, 24.4, 49.7, 21.3, 8.4, 6, null, [self::A_PEANUT]],
            ['gra', 'Crema de cacahuate natural', 16, '1 cucharada', 588, 25.1, 50.0, 20.0, 6.0, 17, null, [self::A_PEANUT]],
            ['gra', 'Semillas de chía', 12, '1 cucharada', 486, 16.5, 30.7, 42.1, 34.4, 16, null, []],
            ['gra', 'Pepitas de calabaza', 9, '1 cucharada', 559, 30.2, 49.1, 10.7, 6.0, 7, null, []],
            ['gra', 'Linaza molida', 7, '1 cucharada', 534, 18.3, 42.2, 28.9, 27.3, 30, null, []],
            ['gra', 'Aceite de oliva', 5, '1 cucharadita', 884, 0, 100, 0, 0, 2, null, []],
            ['gra', 'Aceite de canola', 5, '1 cucharadita', 884, 0, 100, 0, 0, 0, null, []],
            ['gra', 'Mantequilla con sal', 5, '1 cucharadita', 717, 0.9, 81.1, 0.1, 0, 643, null, $milk],

            // ---------------- Azúcares ----------------
            ['azu', 'Miel de abeja', 14, '2 cucharaditas', 304, 0.3, 0, 82.4, 0.2, 4, 61, []],
        ];
    }

    public function run(): void
    {
        $groups = [];
        foreach (self::GROUPS as $key => $name) {
            $groups[$key] = FoodGroup::firstOrCreate(['name' => $name])->id;
        }

        $allergens = Allergen::pluck('id', 'name');
        $legacyByNew = array_flip(self::LEGACY_NAMES);
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        DB::transaction(function () use ($groups, $allergens, $legacyByNew, &$stats) {
            foreach (self::catalog() as [$group, $name, $grams, $portion, $kcal, $protein, $lipids, $carbs, $fiber, $sodium, $gi, $foodAllergens]) {
                $factor = $grams / 100;

                $values = [
                    'food_group_id' => $groups[$group],
                    'name' => $name,
                    'portion_grams' => $grams,
                    'portion_description' => $portion,
                    // Nutrientes de la porción (convención de la tabla)
                    'kcal' => round($kcal * $factor, 2),
                    'protein_g' => round($protein * $factor, 2),
                    'lipids_g' => round($lipids * $factor, 2),
                    'carbs_g' => round($carbs * $factor, 2),
                    'fiber_g' => $fiber === null ? null : round($fiber * $factor, 2),
                    'sodium_mg' => $sodium === null ? null : round($sodium * $factor, 2),
                    'glycemic_index' => $gi,
                    'source' => $name === 'Queso panela' ? 'NutriLife · equivalente SMAE' : self::SOURCE,
                    'active' => true,
                ];

                $food = Food::where('name', $name)->orderBy('id')->first();

                // Alimento del catálogo de prueba anterior con otro nombre
                if (! $food && isset($legacyByNew[$name])) {
                    $food = Food::where('name', $legacyByNew[$name])
                        ->where('source', self::LEGACY_SOURCE)
                        ->orderBy('id')
                        ->first();
                }

                if (! $food) {
                    $food = Food::create($values);
                    $stats['created']++;
                } elseif (in_array($food->source, [self::SOURCE, self::LEGACY_SOURCE, 'NutriLife · equivalente SMAE'], true)) {
                    $food->update($values);
                    $stats['updated']++;
                } else {
                    // Lo cargó alguien más: no se toca.
                    $stats['skipped']++;

                    continue;
                }

                $ids = collect($foodAllergens)->map(fn ($allergen) => $allergens[$allergen] ?? null)->filter()->all();

                if ($ids) {
                    $food->allergens()->syncWithoutDetaching($ids);
                }
            }
        });

        $this->command?->info(sprintf(
            'FoodSeeder: %d alimentos creados, %d actualizados, %d sin tocar (de otra fuente). Total en catálogo: %d.',
            $stats['created'], $stats['updated'], $stats['skipped'], Food::count()
        ));

        if ($allergens->isEmpty()) {
            $this->command?->warn('No hay alérgenos en la BD: ejecuta ClinicalCatalogSeeder y vuelve a correr FoodSeeder para ligarlos.');
        }
    }
}
