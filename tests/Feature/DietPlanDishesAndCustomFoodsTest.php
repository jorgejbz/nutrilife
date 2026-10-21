<?php

namespace Tests\Feature;

use App\Models\DietPlan;
use App\Models\DietPlanDish;
use App\Models\DietPlanDishItem;
use App\Models\DietPlanItem;
use App\Models\DietPlanMeal;
use App\Models\Food;
use App\Models\FoodGroup;
use App\Models\Patient;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\ClinicalCatalogSeeder;
use Database\Seeders\DietPlanDishSeeder;
use Database\Seeders\FoodSeeder;
use Database\Seeders\IncmnszCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Actualización 3.2: alimentos personalizados (food_id NULL + custom_*) y
 * platillos (diet_plan_dishes / diet_plan_dish_items) en los planes.
 */
class DietPlanDishesAndCustomFoodsTest extends TestCase
{
    use RefreshDatabase;

    private Food $oats;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Esencial' => 249, 'Pro' => 399, 'Consultorio' => 599] as $name => $price) {
            SubscriptionPlan::create(['name' => $name, 'price' => $price, 'billing_period' => 'mensual', 'active' => true]);
        }

        $cereals = FoodGroup::create(['name' => 'Cereales']);

        // 117 kcal por porción de 30 g
        $this->oats = Food::create(['food_group_id' => $cereals->id, 'name' => 'Avena', 'portion_grams' => 30, 'portion_description' => '1/3 taza', 'kcal' => 117, 'protein_g' => 5, 'lipids_g' => 2, 'carbs_g' => 20, 'active' => true]);
    }

    private function account(string $plan = 'Pro'): User
    {
        $user = User::factory()->create();

        Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => SubscriptionPlan::where('name', $plan)->value('id'),
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now()->subDays(5)->toDateString(),
            'current_period_ends_at' => now()->addDays(25)->toDateString(),
        ]);

        return $user;
    }

    private function mealOf(User $user): DietPlanMeal
    {
        $patient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Paciente',
            'last_name' => $user->id,
            'email' => 'p'.$user->id.'-'.uniqid().'@test.mx',
            'active' => true,
        ]);

        $plan = DietPlan::create([
            'user_id' => $user->id,
            'patient_id' => $patient->id,
            'name' => 'Plan de '.$user->name,
            'target_kcal' => 1800, 'target_protein_g' => 100, 'target_carbs_g' => 200, 'target_lipids_g' => 60,
            'starts_at' => now()->toDateString(),
            'active' => true,
        ]);

        return $plan->meals()->create(['meal_name' => 'Desayuno', 'meal_order' => 1]);
    }

    private function url(DietPlanMeal $meal, string $suffix): string
    {
        return "/planes-alimenticios/{$meal->diet_plan_id}/comidas/{$meal->id}/{$suffix}";
    }

    private function customData(array $overrides = []): array
    {
        return [
            'custom_name' => 'Tamal de rajas',
            'custom_portion_description' => '1 pieza',
            'grams' => '200',
            'custom_kcal' => '300',
            'custom_protein_g' => '10',
            'custom_carbs_g' => '40',
            'custom_lipids_g' => '12',
            ...$overrides,
        ];
    }

    /** Platillo con un ingrediente del catálogo (60 g de avena) y uno personalizado. */
    private function dish(?User $owner = null, string $name = 'Avena con leche'): DietPlanDish
    {
        $dish = DietPlanDish::create(['user_id' => $owner?->id, 'name' => $name, 'image_url' => 'https://example.com/avena.jpg']);
        $dish->items()->create(['food_id' => $this->oats->id, 'grams' => 60]);
        $dish->items()->create(['custom_name' => 'Leche descremada', 'custom_kcal' => 70, 'custom_protein_g' => 7, 'custom_carbs_g' => 10, 'custom_lipids_g' => 0.5, 'grams' => 200]);

        return $dish;
    }

    /* ------------------------------------------------------------------
     | Alimento personalizado
     * ------------------------------------------------------------------ */

    public function test_custom_food_is_added_shown_and_counted(): void
    {
        $user = $this->account();
        $meal = $this->mealOf($user);

        $this->actingAs($user)->post($this->url($meal, 'alimentos-personalizados'), $this->customData())
            ->assertRedirect()->assertSessionHasNoErrors();

        $item = $meal->items()->firstOrFail();
        $this->assertTrue($item->isCustom());
        $this->assertNull($item->food_id);
        $this->assertSame('Tamal de rajas', $item->custom_name);
        $this->assertEquals(1.0, (float) $item->equivalents);

        $totals = $meal->dietPlan->fresh()->load('meals.items.food')->totals();
        $this->assertEqualsWithDelta(300, $totals['kcal'], 0.001);
        $this->assertEqualsWithDelta(10, $totals['protein_g'], 0.001);

        $this->actingAs($user)->get("/planes-alimenticios/{$meal->diet_plan_id}")
            ->assertOk()->assertSee('Tamal de rajas')->assertSee('Personalizado')->assertSee('1 pieza');
    }

    public function test_custom_food_validation_respects_the_database_limits(): void
    {
        $user = $this->account();
        $meal = $this->mealOf($user);

        $this->actingAs($user)->post($this->url($meal, 'alimentos-personalizados'), [])
            ->assertSessionHasErrors(['custom_name', 'grams', 'custom_kcal', 'custom_protein_g', 'custom_carbs_g', 'custom_lipids_g']);

        $this->actingAs($user)->post($this->url($meal, 'alimentos-personalizados'), $this->customData([
            'custom_name' => str_repeat('a', 256),
            'custom_kcal' => '100000',       // DECIMAL(7,2)
            'custom_protein_g' => '10000',   // DECIMAL(6,2)
            'custom_carbs_g' => '-1',
            'custom_lipids_g' => '1.555',
            'grams' => '10000',              // DECIMAL(6,2)
        ]))->assertSessionHasErrors(['custom_name', 'custom_kcal', 'custom_protein_g', 'custom_carbs_g', 'custom_lipids_g', 'grams']);

        $this->assertDatabaseCount('diet_plan_items', 0);
    }

    public function test_changing_grams_of_a_custom_food_rescales_its_contribution(): void
    {
        $user = $this->account();
        $meal = $this->mealOf($user);
        $item = $meal->items()->create(['custom_name' => 'Tamal', 'custom_kcal' => 300, 'custom_protein_g' => 10, 'custom_carbs_g' => 40, 'custom_lipids_g' => 12, 'grams' => 200, 'equivalents' => 1]);

        $this->actingAs($user)->patch($this->url($meal, "alimentos/{$item->id}"), ['grams' => '100'])
            ->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertEquals(100.0, (float) $item->grams);
        $this->assertEquals(150.0, (float) $item->custom_kcal);
        $this->assertEquals(5.0, (float) $item->custom_protein_g);
        $this->assertEquals(20.0, (float) $item->custom_carbs_g);
        $this->assertEquals(6.0, (float) $item->custom_lipids_g);

        // Reescalar no puede pasar los límites: 2,000 kcal en 100 g → 9,999 g ≈ 199,980 kcal > DECIMAL(7,2)
        $dense = $meal->items()->create(['custom_name' => 'Pastel', 'custom_kcal' => 2000, 'custom_protein_g' => 20, 'custom_carbs_g' => 250, 'custom_lipids_g' => 100, 'grams' => 100, 'equivalents' => 1]);
        $this->actingAs($user)->patch($this->url($meal, "alimentos/{$dense->id}"), ['grams' => '9999'])
            ->assertSessionHasErrors('grams');
        $this->assertEquals(2000.0, (float) $dense->fresh()->custom_kcal);
        $this->assertEquals(100.0, (float) $dense->fresh()->grams);

        // Un valor sin dato (NULL) sigue sin dato
        $unknown = $meal->items()->create(['custom_name' => 'Aceitunas', 'grams' => 20, 'equivalents' => 1]);
        $this->actingAs($user)->patch($this->url($meal, "alimentos/{$unknown->id}"), ['grams' => '40'])
            ->assertSessionHasNoErrors();
        $this->assertNull($unknown->fresh()->custom_kcal);
        $this->assertFalse($unknown->fresh()->hasNutrients());
    }

    /* ------------------------------------------------------------------
     | Platillos
     * ------------------------------------------------------------------ */

    public function test_a_dish_is_copied_into_the_meal(): void
    {
        $user = $this->account();
        $meal = $this->mealOf($user);
        $dish = $this->dish();

        $this->actingAs($user)->post($this->url($meal, 'platillos'), ['diet_plan_dish_id' => $dish->id])
            ->assertRedirect()->assertSessionHasNoErrors();

        $items = $meal->items()->get();
        $this->assertCount(2, $items);
        $this->assertTrue($items->every(fn (DietPlanItem $item) => $item->diet_plan_dish_id === $dish->id));

        // Del catálogo: solo food_id y equivalentes (60 g / 30 g = 2); macros del catálogo
        $fromCatalog = $items->firstWhere('food_id', $this->oats->id);
        $this->assertEquals(2.0, (float) $fromCatalog->equivalents);
        $this->assertNull($fromCatalog->custom_name);
        $this->assertNull($fromCatalog->custom_kcal);

        // Personalizado: se copian sus valores tal cual
        $custom = $items->firstWhere('custom_name', 'Leche descremada');
        $this->assertNull($custom->food_id);
        $this->assertEquals(70.0, (float) $custom->custom_kcal);
        $this->assertSame('https://example.com/avena.jpg', $custom->custom_image_url);

        // 234 kcal de avena + 70 de leche
        $this->assertEqualsWithDelta(304, $meal->dietPlan->fresh()->load('meals.items.food')->totals()['kcal'], 0.001);

        $this->actingAs($user)->get("/planes-alimenticios/{$meal->diet_plan_id}")
            ->assertOk()->assertSee('Leche descremada')->assertSee('Avena con leche');

        // Es copia: cambiar el platillo no cambia el plan
        $dish->items()->where('custom_name', 'Leche descremada')->update(['custom_kcal' => 999]);
        $this->assertEquals(70.0, (float) $custom->fresh()->custom_kcal);

        // Borrar el platillo: sus ingredientes se van, el plan conserva su copia
        $dish->delete();
        $this->assertSame(0, DietPlanDishItem::count());
        $this->assertSame(2, $meal->items()->count());
        $this->assertNull($custom->fresh()->diet_plan_dish_id);
    }

    public function test_empty_or_foreign_dishes_cannot_be_added(): void
    {
        $user = $this->account();
        $stranger = $this->account();
        $meal = $this->mealOf($user);

        $empty = DietPlanDish::create(['name' => 'Platillo sin receta']);
        $this->actingAs($user)->post($this->url($meal, 'platillos'), ['diet_plan_dish_id' => $empty->id])
            ->assertSessionHasErrors('diet_plan_dish_id');

        $foreign = $this->dish($stranger, 'Receta secreta ajena');
        $this->actingAs($user)->post($this->url($meal, 'platillos'), ['diet_plan_dish_id' => $foreign->id])
            ->assertSessionHasErrors('diet_plan_dish_id');

        $this->actingAs($user)->post($this->url($meal, 'platillos'), ['diet_plan_dish_id' => 9999])
            ->assertSessionHasErrors('diet_plan_dish_id');

        $this->assertDatabaseCount('diet_plan_items', 0);

        // Propio sí; y en el constructor solo aparecen los del sistema y los propios
        $own = $this->dish($user, 'Mi avena');
        $this->actingAs($user)->post($this->url($meal, 'platillos'), ['diet_plan_dish_id' => $own->id])
            ->assertSessionHasNoErrors();
        $this->assertSame(2, $meal->items()->count());

        $this->actingAs($user)->get("/planes-alimenticios/{$meal->diet_plan_id}")
            ->assertOk()
            ->assertSee('Mi avena')
            ->assertSee('Platillo sin receta')
            ->assertSee('Sin ingredientes registrados')
            ->assertDontSee('Receta secreta ajena');
    }

    public function test_duplicating_a_plan_keeps_custom_foods_and_dish_origin(): void
    {
        $user = $this->account();
        $meal = $this->mealOf($user);
        $dish = $this->dish();

        $this->actingAs($user)->post($this->url($meal, 'platillos'), ['diet_plan_dish_id' => $dish->id]);
        $this->actingAs($user)->post($this->url($meal, 'alimentos-personalizados'), $this->customData());

        $this->actingAs($user)->post("/planes-alimenticios/{$meal->diet_plan_id}/duplicar", [
            'patient_id' => $meal->dietPlan->patient_id, 'name' => 'Copia', 'starts_at' => now()->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $copyItems = DietPlan::where('name', 'Copia')->firstOrFail()->meals()->firstOrFail()->items()->get();
        $this->assertCount(3, $copyItems);
        $this->assertSame(['Leche descremada', 'Tamal de rajas'], $copyItems->whereNull('food_id')->pluck('custom_name')->sort()->values()->all());
        $this->assertSame(2, $copyItems->where('diet_plan_dish_id', $dish->id)->count());
        $this->assertSame(3, $meal->items()->count(), 'El original no cambia.');
    }

    public function test_new_routes_keep_account_isolation_and_plan_permissions(): void
    {
        $owner = $this->account('Consultorio');
        $intruder = $this->account('Consultorio');
        $meal = $this->mealOf($owner);
        $dish = $this->dish();

        $this->actingAs($intruder)->post($this->url($meal, 'alimentos-personalizados'), $this->customData())->assertNotFound();
        $this->actingAs($intruder)->post($this->url($meal, 'platillos'), ['diet_plan_dish_id' => $dish->id])->assertNotFound();

        // Comida ajena colgada de un plan propio → 404 (scopeBindings)
        $intruderMeal = $this->mealOf($intruder);
        $this->actingAs($intruder)->post("/planes-alimenticios/{$intruderMeal->diet_plan_id}/comidas/{$meal->id}/platillos", ['diet_plan_dish_id' => $dish->id])
            ->assertNotFound();

        // Esencial no incluye planes alimenticios (aunque el plan sea suyo, p. ej. bajó de Pro)
        $esencial = $this->account('Esencial');
        $esencialMeal = $this->mealOf($esencial);
        $this->actingAs($esencial)->post($this->url($esencialMeal, 'alimentos-personalizados'), $this->customData())->assertForbidden();
        $this->actingAs($esencial)->post($this->url($esencialMeal, 'platillos'), ['diet_plan_dish_id' => $dish->id])->assertForbidden();
        $this->assertSame(0, $esencialMeal->items()->count());

        // Sin sesión {plan} no se resuelve (Route::bind filtra por el usuario autenticado)
        $this->post($this->url($meal, 'platillos'), ['diet_plan_dish_id' => $dish->id])->assertNotFound();

        $this->assertSame(0, $meal->items()->count());
    }

    public function test_dish_seeder_loads_complete_recipes_and_is_idempotent(): void
    {
        $this->seed([ClinicalCatalogSeeder::class, FoodSeeder::class, IncmnszCatalogSeeder::class]);
        $this->seed(DietPlanDishSeeder::class);

        $countAfterFirstRun = DietPlanDishItem::count();
        $firstIds = DietPlanDishItem::orderBy('id')->pluck('id');

        $this->seed(DietPlanDishSeeder::class);

        // Todos los platillos del sistema, con receta y con todos sus datos
        $this->assertSame(count(DietPlanDishSeeder::DISHES), DietPlanDish::whereNull('user_id')->count());
        $this->assertSame($countAfterFirstRun, DietPlanDishItem::count());
        $this->assertEquals($firstIds, DietPlanDishItem::orderBy('id')->pluck('id'), 'La segunda corrida no debe reemplazar recetas iguales');

        DietPlanDish::with('items.food')->get()->each(function (DietPlanDish $dish) {
            $this->assertNotEmpty($dish->items, $dish->name.' sin ingredientes');
            $this->assertSame(0, $dish->missingNutrientsCount(), $dish->name.' con ingredientes sin datos');
            $this->assertGreaterThan(150, $dish->totals()['kcal'], $dish->name);
        });

        // Ingrediente del catálogo: kcal de foods (150 g de pechuga = 5 porciones de 30 g)
        $chicken = DietPlanDish::where('name', 'Pechuga a la Plancha con Papa al Horno y Ensalada')->firstOrFail()
            ->items()->with('food')->get()->firstWhere('food.name', 'Pechuga de pollo sin piel (cocida)');
        $this->assertEqualsWithDelta(5 * (float) $chicken->food->kcal, $chicken->nutrients()['kcal'], 0.01);
        $this->assertNull($chicken->custom_kcal);

        // Personalizado: valores por 100 g escalados a sus gramos (100 g de tofu)
        $tofu = DietPlanDishItem::where('custom_name', 'Tofu firme')->firstOrFail();
        $this->assertEquals(144.0, (float) $tofu->custom_kcal);
    }

    public function test_every_system_dish_has_its_own_local_photo_and_credit(): void
    {
        $this->seed([ClinicalCatalogSeeder::class, FoodSeeder::class, IncmnszCatalogSeeder::class]);

        // Platillo existente con la foto genérica anterior: el seeder la corrige,
        // también en la copia que ya estaba en un plan
        $oldPhoto = 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=300';
        DietPlanDish::create(['name' => 'Bowl de Quinoa con Verduras Salteadas y Tofu', 'image_url' => $oldPhoto]);
        $copied = $this->mealOf($this->account())->items()->create(['custom_name' => 'Tofu', 'custom_kcal' => 144, 'custom_protein_g' => 17, 'custom_carbs_g' => 3, 'custom_lipids_g' => 9, 'grams' => 100, 'custom_image_url' => $oldPhoto]);

        $this->seed(DietPlanDishSeeder::class);

        $this->assertSame('images/platillos/bowl-de-quinoa-con-verduras-salteadas-y-tofu.jpg', $copied->fresh()->custom_image_url);
        $this->assertEquals(144.0, (float) $copied->fresh()->custom_kcal);

        $dishes = DietPlanDish::whereNull('user_id')->get();
        $this->assertCount(count(DietPlanDishSeeder::DISHES), $dishes);
        $this->assertCount(count(DietPlanDishSeeder::DISHES), $dishes->pluck('image_url')->unique(), 'Cada platillo tiene su propia foto');

        foreach ($dishes as $dish) {
            $this->assertStringStartsWith('images/platillos/', $dish->image_url, $dish->name);
            $this->assertFileExists(public_path($dish->image_url), $dish->name);
            $this->assertNotEmpty($dish->image_credit, $dish->name);
            $this->assertSame(asset($dish->image_url), $dish->imageSrc());
        }

        // Una URL externa se usa tal cual
        $this->assertSame('https://example.com/a.jpg', (new DietPlanDish(['image_url' => 'https://example.com/a.jpg']))->imageSrc());

        // El detalle del catálogo muestra el crédito
        $quinoa = $dishes->firstWhere('name', 'Bowl de Quinoa con Verduras Salteadas y Tofu');
        $this->actingAs($this->account())->get('/platillos')
            ->assertSee(asset($quinoa->image_url), false)
            ->assertSee('Foto: '.$quinoa->image_credit);
    }

    public function test_dish_seeder_replaces_an_incomplete_system_recipe_but_not_own_dishes(): void
    {
        $this->seed([ClinicalCatalogSeeder::class, FoodSeeder::class, IncmnszCatalogSeeder::class]);

        $salmon = DietPlanDish::create(['name' => 'Filete de Salmón al Horno con Espárragos y Camote']);
        $salmon->items()->create(['custom_name' => 'Filete de Salmón Fresco', 'grams' => 180]);

        $own = DietPlanDish::create(['user_id' => $this->account()->id, 'name' => 'Filete de Salmón al Horno con Espárragos y Camote']);
        $own->items()->create(['custom_name' => 'Mi salmón', 'grams' => 200]);

        $this->seed(DietPlanDishSeeder::class);

        $this->assertSame(4, $salmon->items()->count());
        $this->assertNull($salmon->items()->where('custom_name', 'Filete de Salmón Fresco')->first());
        $this->assertSame(['Mi salmón'], $own->items()->pluck('custom_name')->all());
    }
}
