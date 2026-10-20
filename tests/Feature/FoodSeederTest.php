<?php

namespace Tests\Feature;

use App\Models\Food;
use App\Models\FoodGroup;
use Database\Seeders\ClinicalCatalogSeeder;
use Database\Seeders\FoodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FoodSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_fills_the_catalog_and_can_run_twice_without_duplicates(): void
    {
        $this->seed(ClinicalCatalogSeeder::class);
        $this->seed(FoodSeeder::class);
        $count = Food::count();
        $links = DB::table('food_allergens')->count();

        $this->assertGreaterThanOrEqual(60, $count);
        $this->assertLessThanOrEqual(100, $count);
        $this->assertGreaterThan(0, $links);

        $this->seed(FoodSeeder::class);
        $this->assertSame($count, Food::count());
        $this->assertSame($links, DB::table('food_allergens')->count());
        $this->assertSame(0, DB::table('foods')->select('name')->groupBy('name')->havingRaw('count(*) > 1')->count());

        // Valores por porción (30 g de tortilla de maíz ≈ 65 kcal)
        $tortilla = Food::where('name', 'Tortilla de maíz')->firstOrFail();
        $this->assertEquals(30, (float) $tortilla->portion_grams);
        $this->assertEqualsWithDelta(65, (float) $tortilla->kcal, 3);
        $this->assertSame('Cereales y tubérculos', $tortilla->foodGroup->name);
    }

    public function test_seeder_does_not_touch_foods_from_other_sources_and_migrates_the_old_test_catalog(): void
    {
        $group = FoodGroup::create(['name' => 'Mi grupo']);
        $own = Food::create(['food_group_id' => $group->id, 'name' => 'Manzana', 'portion_grams' => 100, 'portion_description' => 'mi dato',
            'kcal' => 50, 'protein_g' => 0, 'lipids_g' => 0, 'carbs_g' => 13, 'source' => 'INCAP-SMAE', 'active' => true]);
        $legacy = Food::create(['food_group_id' => $group->id, 'name' => 'Arroz cocido', 'portion_grams' => 50, 'portion_description' => '1/4 taza',
            'kcal' => 70, 'protein_g' => 2, 'lipids_g' => 0, 'carbs_g' => 15, 'source' => 'Prueba (promedio SMAE)', 'active' => true]);

        $this->seed(FoodSeeder::class);

        $own->refresh();
        $this->assertSame('mi dato', $own->portion_description);
        $this->assertSame('INCAP-SMAE', $own->source);
        $this->assertSame(1, Food::where('name', 'Manzana')->count());

        $legacy->refresh();
        $this->assertSame('Arroz blanco (cocido)', $legacy->name);
        $this->assertSame(0, Food::where('name', 'Arroz cocido')->count());
    }
}
