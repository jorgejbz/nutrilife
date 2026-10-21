<?php

namespace Tests\Feature;

use App\Models\Allergen;
use App\Models\DietPlanDish;
use App\Models\Food;
use App\Models\FoodGroup;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Catálogo de platillos (/platillos): solo consulta, mismo acceso que Planes
 * alimenticios; platillos del sistema + propios; búsqueda, filtros y aporte.
 */
class DishCatalogTest extends TestCase
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

    /** 60 g de avena (234 kcal) + leche personalizada (70 kcal) = 304 kcal. */
    private function oatsDish(?User $owner = null, string $name = 'Avena con leche'): DietPlanDish
    {
        $dish = DietPlanDish::create(['user_id' => $owner?->id, 'name' => $name]);
        $dish->items()->create(['food_id' => $this->oats->id, 'grams' => 60]);
        $dish->items()->create(['custom_name' => 'Leche descremada', 'custom_kcal' => 70, 'custom_protein_g' => 7, 'custom_carbs_g' => 10, 'custom_lipids_g' => 0.5, 'grams' => 200]);

        return $dish;
    }

    public function test_lists_system_and_own_dishes_with_their_totals(): void
    {
        $user = $this->account();
        $this->oatsDish();
        $this->oatsDish($user, 'Mi avena');
        $this->oatsDish($this->account(), 'Receta secreta ajena');
        DietPlanDish::create(['name' => 'Platillo sin receta']);

        $this->actingAs($user)->get('/platillos')
            ->assertOk()
            ->assertSee('Catálogo de platillos')
            ->assertSee('Avena con leche')
            ->assertSee('Mi avena')
            ->assertSee('Mi platillo')
            ->assertSee('Platillo sin receta')
            ->assertSee('Sin ingredientes registrados')
            ->assertSee('304')
            ->assertSee('Leche descremada')
            ->assertDontSee('Receta secreta ajena');
    }

    public function test_search_matches_dish_or_ingredient_names(): void
    {
        $user = $this->account();
        $this->oatsDish();
        DietPlanDish::create(['name' => 'Ceviche de pescado']);

        $this->actingAs($user)->get('/platillos?q=leche+descremada')
            ->assertSee('Avena con leche')->assertDontSee('Ceviche de pescado');

        $this->actingAs($user)->get('/platillos?q=ceviche')
            ->assertSee('Ceviche de pescado')->assertDontSee('Avena con leche');

        $this->actingAs($user)->get('/platillos?q=zzz')->assertSee('Ningún platillo coincide');
    }

    public function test_filters_by_origin_data_and_allergen(): void
    {
        $user = $this->account();
        $this->oatsDish();
        $this->oatsDish($user, 'Mi avena');
        DietPlanDish::create(['name' => 'Platillo sin receta']);

        $gluten = Allergen::create(['name' => 'Gluten']);
        $this->oats->allergens()->attach($gluten->id);

        $this->actingAs($user)->get('/platillos?origen=propios')
            ->assertSee('Mi avena')->assertDontSee('Avena con leche');

        $this->actingAs($user)->get('/platillos?datos=sin_ingredientes')
            ->assertSee('Platillo sin receta')->assertDontSee('Mi avena');

        $this->actingAs($user)->get('/platillos?sin='.$gluten->id)
            ->assertSee('Platillo sin receta')->assertDontSee('Mi avena')->assertDontSee('Avena con leche');
    }

    public function test_paginates_twelve_per_page(): void
    {
        $user = $this->account();
        foreach (range(1, 14) as $number) {
            $this->oatsDish(null, sprintf('Platillo %02d', $number));
        }

        $this->actingAs($user)->get('/platillos')
            ->assertSee('Mostrando 1–12 de 14')->assertSee('Página 1 de 2')->assertDontSee('Platillo 13');

        $this->actingAs($user)->get('/platillos?page=2')->assertSee('Platillo 13')->assertSee('Platillo 14');
    }

    public function test_access_follows_the_diet_plans_feature(): void
    {
        $this->get('/platillos')->assertRedirect('/login');
        $this->actingAs($this->account('Esencial'))->get('/platillos')->assertForbidden();
    }

    public function test_menu_links_to_the_dish_catalog_with_the_feature(): void
    {
        // (prueba aparte: AdminLTE arma el menú una vez por aplicación)
        $this->actingAs($this->account())->get('/dashboard')
            ->assertSee('href="'.route('dishes.index').'"', false);
    }

    public function test_empty_catalog_shows_a_friendly_state(): void
    {
        $this->actingAs($this->account())->get('/platillos')
            ->assertOk()->assertSee('El catálogo aún no tiene platillos');
    }
}
