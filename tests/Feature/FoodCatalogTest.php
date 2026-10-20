<?php

namespace Tests\Feature;

use App\Models\Allergen;
use App\Models\DietPlan;
use App\Models\Food;
use App\Models\Patient;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\ClinicalCatalogSeeder;
use Database\Seeders\FoodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Página "Catálogo de alimentos" (/alimentos) y su enlace con el
 * diálogo "Agregar alimento" de los planes.
 */
class FoodCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Esencial' => 249, 'Pro' => 399] as $name => $price) {
            SubscriptionPlan::create(['name' => $name, 'price' => $price, 'billing_period' => 'mensual', 'active' => true]);
        }
    }

    private function nutritionist(string $plan = 'Pro'): User
    {
        $user = User::factory()->create();

        Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => SubscriptionPlan::where('name', $plan)->value('id'),
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now()->subDays(10)->toDateString(),
            'current_period_ends_at' => now()->addDays(15)->toDateString(),
        ]);

        return $user;
    }

    private function seedCatalog(): void
    {
        $this->seed(ClinicalCatalogSeeder::class);
        $this->seed(FoodSeeder::class);
    }

    public function test_catalog_lists_real_foods_with_count_and_nutrients(): void
    {
        $this->seedCatalog();
        $total = Food::where('active', true)->count();
        $tortilla = Food::where('name', 'Tortilla de maíz')->firstOrFail();

        $this->actingAs($this->nutritionist())->get('/alimentos')
            ->assertOk()
            ->assertSee('Catálogo de alimentos')
            ->assertSee($total.' alimentos')
            ->assertSee('Mostrando 1–24 de '.$total)
            ->assertSee('Página 1 de '.(int) ceil($total / 24))
            ->assertSee('rel="next"', false);

        $this->actingAs($this->nutritionist())->get('/alimentos?q=tortilla de maiz')
            ->assertOk()
            ->assertSee('Tortilla de maíz')
            ->assertSee('1 pieza · 30 g')
            ->assertSee(number_format((float) $tortilla->kcal, 0))
            ->assertSee(rtrim(rtrim(number_format((float) $tortilla->carbs_g, 1), '0'), '.').' g')
            ->assertDontSee('Tortilla de harina');
    }

    public function test_search_and_filters_use_the_database_and_can_be_cleared(): void
    {
        $this->seedCatalog();
        $user = $this->nutritionist();

        // Sin acento encuentra el nombre con acento; varias palabras en cualquier orden
        $this->actingAs($user)->get('/alimentos?q=platano')->assertSee('Plátano');
        $this->actingAs($user)->get('/alimentos?q=pollo pechuga')
            ->assertSee('Pechuga de pollo sin piel (cocida)')
            ->assertDontSee('Muslo de pollo');

        // Sin coincidencias
        $this->actingAs($user)->get('/alimentos?q=xyzzy')
            ->assertOk()
            ->assertSee('Ningún alimento coincide')
            ->assertSee('Limpiar filtros');

        // Grupo
        $fruits = \App\Models\FoodGroup::where('name', 'Frutas')->firstOrFail();
        $response = $this->actingAs($user)->get('/alimentos?grupo='.$fruits->id)
            ->assertOk()
            ->assertSee('Grupo: Frutas')
            ->assertSee('Mango')
            ->assertDontSee('Pechuga de pollo');
        $this->assertSame($fruits->foods()->where('active', true)->count(), $response->viewData('foods')->total());

        // Excluir alérgeno
        $gluten = Allergen::where('name', 'like', '%gluten%')->firstOrFail();
        $this->actingAs($user)->get('/alimentos?q=pan&sin='.$gluten->id)
            ->assertDontSee('Pan integral')
            ->assertSee('Sin gluten');

        // Orden por proteína
        $first = $this->actingAs($user)->get('/alimentos?orden=proteina')->viewData('foods')->first();
        $this->assertEquals(Food::where('active', true)->max('protein_g'), $first->protein_g);

        // Valores fuera de rango se ignoran (no rompen la página)
        $this->actingAs($user)->get('/alimentos?grupo=99999&sin=99999&orden=hack')
            ->assertOk()
            ->assertDontSee('Filtros activos');
    }

    public function test_inactive_foods_are_not_listed(): void
    {
        $this->seedCatalog();
        Food::where('name', 'Mango')->update(['active' => false]);

        $this->actingAs($this->nutritionist())->get('/alimentos?q=mango')
            ->assertSee('Ningún alimento coincide');
    }

    public function test_empty_catalog_and_database_error_have_their_own_states(): void
    {
        $user = $this->nutritionist();

        $this->actingAs($user)->get('/alimentos')
            ->assertOk()
            ->assertSee('El catálogo aún no tiene alimentos')
            ->assertDontSee('No se pudo cargar');

        Schema::drop('food_allergens');

        $this->actingAs($user)->get('/alimentos')
            ->assertStatus(503)
            ->assertSee('No se pudo cargar el catálogo')
            ->assertSee('Reintentar');
    }

    public function test_access_follows_the_diet_plans_feature(): void
    {
        $this->get('/alimentos')->assertRedirect(route('login'));

        $this->actingAs($this->nutritionist('Esencial'))->get('/alimentos')
            ->assertForbidden()
            ->assertSee('no está incluida en tu plan');

        // Enlace del menú lateral solo para quien tiene la función
        $this->actingAs($this->nutritionist('Esencial'))->get('/dashboard')
            ->assertDontSee('href="'.route('foods.index').'"', false);
    }

    public function test_menu_shows_the_catalog_link_to_plans_with_the_feature(): void
    {
        // (prueba aparte: AdminLTE arma el menú una vez por aplicación)
        $this->actingAs($this->nutritionist('Pro'))->get('/dashboard')
            ->assertSee('href="'.route('foods.index').'"', false);
    }

    public function test_plan_builder_dialog_still_lists_the_catalog_and_links_to_it(): void
    {
        $this->seedCatalog();
        $user = $this->nutritionist();
        $patient = new Patient;
        $patient->forceFill(['user_id' => $user->id, 'first_name' => 'Ana', 'last_name' => 'Ruiz', 'email' => 'ana@t.mx'])->save();
        $plan = DietPlan::create(['user_id' => $user->id, 'patient_id' => $patient->id, 'name' => 'Plan', 'starts_at' => now()->toDateString(), 'target_kcal' => 1800, 'target_protein_g' => 90, 'target_carbs_g' => 220, 'target_lipids_g' => 60, 'active' => true]);
        $plan->meals()->create(['meal_name' => 'Desayuno', 'sort_order' => 1]);

        $this->actingAs($user)->get(route('diet-plans.show', $plan))
            ->assertOk()
            ->assertDontSee('está vacío')
            ->assertSee('id="foodGroupSelect"', false)
            ->assertSee('Ver catálogo completo')
            ->assertSee(route('foods.index'), false)
            ->assertSee(Food::where('active', true)->count().' alimentos');
    }
}
