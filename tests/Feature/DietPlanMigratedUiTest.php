<?php

namespace Tests\Feature;

use App\Models\DietPlan;
use App\Models\DietPlanDish;
use App\Models\DietPlanMeal;
use App\Models\Food;
use App\Models\FoodGroup;
use App\Models\Patient;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Interfaz migrada de nutrilife_planesAlim: banner y lista, formulario por
 * pasos, constructor con drawers (alimento, manual, platillo, duplicar) y
 * reapertura del drawer cuando la validación falla.
 */
class DietPlanMigratedUiTest extends TestCase
{
    use RefreshDatabase;

    private Food $apple;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Esencial' => 249, 'Pro' => 399, 'Consultorio' => 599] as $name => $price) {
            SubscriptionPlan::create(['name' => $name, 'price' => $price, 'billing_period' => 'mensual', 'active' => true]);
        }

        $fruits = FoodGroup::create(['name' => 'Frutas']);

        $this->apple = Food::create(['food_group_id' => $fruits->id, 'name' => 'Manzana', 'portion_grams' => 106, 'portion_description' => '1 pieza chica', 'kcal' => 55, 'protein_g' => 0.3, 'lipids_g' => 0.2, 'carbs_g' => 14.6, 'active' => true]);
    }

    private function account(): User
    {
        $user = User::factory()->create();

        Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => SubscriptionPlan::where('name', 'Pro')->value('id'),
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now()->subDays(5)->toDateString(),
            'current_period_ends_at' => now()->addDays(25)->toDateString(),
        ]);

        return $user;
    }

    private function mealOf(User $user): DietPlanMeal
    {
        $patient = Patient::create(['user_id' => $user->id, 'first_name' => 'Ana', 'last_name' => 'Ruiz', 'email' => uniqid().'@test.mx', 'active' => true]);

        $plan = DietPlan::create([
            'user_id' => $user->id, 'patient_id' => $patient->id, 'name' => 'Plan migrado',
            'target_kcal' => 1800, 'target_protein_g' => 100, 'target_carbs_g' => 200, 'target_lipids_g' => 60,
            'starts_at' => now()->toDateString(), 'active' => true,
        ]);

        return $plan->meals()->create(['meal_name' => 'Desayuno', 'meal_order' => 1]);
    }

    public function test_index_uses_the_migrated_layout(): void
    {
        $user = $this->account();
        $this->mealOf($user);

        $this->actingAs($user)->get('/planes-alimenticios')
            ->assertOk()
            ->assertSee('Alimenta sus metas')
            ->assertSee('Planes finalizados')
            ->assertSee('Ver / editar plan')
            ->assertSee('Plan migrado')
            ->assertSee('css/diet-plans.css', false);
    }

    public function test_create_and_edit_use_the_step_form(): void
    {
        $user = $this->account();
        $meal = $this->mealOf($user);

        $this->actingAs($user)->get('/planes-alimenticios/crear')
            ->assertOk()
            ->assertSee('PASO 01 · PACIENTE')
            ->assertSee('PASO 03 · NUTRICIÓN')
            ->assertSee('id="dietPlanForm"', false);

        $this->actingAs($user)->get("/planes-alimenticios/{$meal->diet_plan_id}/editar")
            ->assertOk()
            ->assertSee('Editar plan alimenticio')
            ->assertSee('id="planActive"', false)
            ->assertDontSee('name="patient_id"', false);
    }

    public function test_builder_renders_drawers_with_the_routes_of_each_meal(): void
    {
        $user = $this->account();
        $meal = $this->mealOf($user);
        $dish = DietPlanDish::create(['name' => 'Avena con manzana']);
        $dish->items()->create(['food_id' => $this->apple->id, 'grams' => 106]);

        $base = "/planes-alimenticios/{$meal->diet_plan_id}/comidas/{$meal->id}";

        $this->actingAs($user)->get("/planes-alimenticios/{$meal->diet_plan_id}")
            ->assertOk()
            ->assertSee('CONSTRUCTOR NUTRICIONAL')
            ->assertSee('id="foodDrawer"', false)
            ->assertSee('id="dishDrawer"', false)
            ->assertSee('id="customFoodForm"', false)
            ->assertSee('id="duplicateDrawer"', false)
            ->assertSee('data-action="'.url($base.'/alimentos').'"', false)
            ->assertSee('data-custom-action="'.url($base.'/alimentos-personalizados').'"', false)
            ->assertSee('data-action="'.url($base.'/platillos').'"', false)
            ->assertSee('data-portion="106.00"', false)
            ->assertSee('data-dish-id="'.$dish->id.'"', false)
            ->assertSee('name="diet_plan_dish_id"', false);
    }

    public function test_failed_validation_reopens_the_same_drawer_and_meal(): void
    {
        $user = $this->account();
        $meal = $this->mealOf($user);
        $show = "/planes-alimenticios/{$meal->diet_plan_id}";

        $this->actingAs($user)->from($show)
            ->post("{$show}/comidas/{$meal->id}/alimentos-personalizados", ['_drawer' => 'custom', '_drawer_meal' => $meal->id])
            ->assertRedirect($show);

        $this->actingAs($user)->get($show)
            ->assertSee('data-reopen-meal="'.$meal->id.'" data-reopen-panel="custom"', false);

        $this->actingAs($user)->from($show)
            ->post("{$show}/comidas/{$meal->id}/platillos", ['_drawer' => 'dish', '_drawer_meal' => $meal->id]);

        $this->actingAs($user)->get($show)
            ->assertSee('data-reopen-meal="'.$meal->id.'"', false)
            ->assertSee('Selecciona un platillo.');
    }

    public function test_deleting_from_the_list_archives_the_plan(): void
    {
        $user = $this->account();
        $meal = $this->mealOf($user);

        $this->actingAs($user)->delete("/planes-alimenticios/{$meal->diet_plan_id}")
            ->assertRedirect('/planes-alimenticios');

        $this->assertSoftDeleted('diet_plans', ['id' => $meal->diet_plan_id]);
        $this->assertDatabaseHas('diet_plan_meals', ['id' => $meal->id]);
    }
}
