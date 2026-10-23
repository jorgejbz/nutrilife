<?php

namespace Tests\Feature;

use App\Models\DietPlan;
use App\Models\DietPlanDish;
use App\Models\DietPlanItem;
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
 * Personalizar los ingredientes de un platillo dentro de una comida:
 * agregar, quitar, sustituir, cambiar cantidad/unidad y porciones, sin
 * tocar la receta del catálogo ni los planes de otros pacientes.
 */
class DietPlanDishCustomizationTest extends TestCase
{
    use RefreshDatabase;

    private Food $chicken;

    private Food $onion;

    private Food $avocado;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Esencial' => 249, 'Pro' => 399, 'Consultorio' => 599] as $name => $price) {
            SubscriptionPlan::create(['name' => $name, 'price' => $price, 'billing_period' => 'mensual', 'active' => true]);
        }

        $group = FoodGroup::create(['name' => 'Varios']);

        // Valores por porción
        $this->chicken = Food::create(['food_group_id' => $group->id, 'name' => 'Pechuga de pollo', 'portion_grams' => 30, 'portion_description' => '30 g', 'kcal' => 40, 'protein_g' => 7, 'lipids_g' => 1, 'carbs_g' => 0, 'active' => true]);
        $this->onion = Food::create(['food_group_id' => $group->id, 'name' => 'Cebolla', 'portion_grams' => 50, 'portion_description' => '1/2 taza', 'kcal' => 20, 'protein_g' => 0.5, 'lipids_g' => 0, 'carbs_g' => 4, 'active' => true]);
        $this->avocado = Food::create(['food_group_id' => $group->id, 'name' => 'Aguacate', 'portion_grams' => 30, 'portion_description' => '1/3 pieza', 'kcal' => 50, 'protein_g' => 0.5, 'lipids_g' => 5, 'carbs_g' => 1, 'active' => true]);
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
            'last_name' => (string) $user->id,
            'email' => 'p'.$user->id.'-'.uniqid().'@test.mx',
            'active' => true,
        ]);

        $plan = DietPlan::create([
            'user_id' => $user->id,
            'patient_id' => $patient->id,
            'name' => 'Plan '.uniqid(),
            'target_kcal' => 1800, 'target_protein_g' => 100, 'target_carbs_g' => 200, 'target_lipids_g' => 60,
            'starts_at' => now()->toDateString(),
            'active' => true,
        ]);

        return $plan->meals()->create(['meal_name' => 'Comida', 'meal_order' => 1]);
    }

    private function base(DietPlanMeal $meal): string
    {
        return "/planes-alimenticios/{$meal->diet_plan_id}/comidas/{$meal->id}";
    }

    /** Pollo 120 g, cebolla 50 g, aguacate 30 g (sistema). */
    private function dish(): DietPlanDish
    {
        $dish = DietPlanDish::create(['user_id' => null, 'name' => 'Tacos de pollo', 'image_url' => 'https://example.com/tacos.jpg']);
        $dish->items()->create(['food_id' => $this->chicken->id, 'grams' => 120]);
        $dish->items()->create(['food_id' => $this->onion->id, 'grams' => 50]);
        $dish->items()->create(['food_id' => $this->avocado->id, 'grams' => 30]);

        return $dish;
    }

    private function addDish(User $user, DietPlanMeal $meal, DietPlanDish $dish): void
    {
        $this->actingAs($user)->post($this->base($meal).'/platillos', ['diet_plan_dish_id' => $dish->id])
            ->assertSessionHasNoErrors();
    }

    private function row(DietPlanMeal $meal, Food $food): DietPlanItem
    {
        return $meal->items()->where('food_id', $food->id)->firstOrFail();
    }

    public function test_ingredients_can_be_removed_substituted_added_and_rescaled_only_in_this_plan(): void
    {
        $user = $this->account();
        $meal = $this->mealOf($user);
        $otherMeal = $this->mealOf($user); // plan de otro paciente con el mismo platillo
        $dish = $this->dish();

        $this->addDish($user, $meal, $dish);
        $this->addDish($user, $otherMeal, $dish);

        // Quitar la cebolla
        $onion = $this->row($meal, $this->onion);
        $this->actingAs($user)->delete($this->base($meal).'/alimentos/'.$onion->id)
            ->assertRedirect(route('diet-plans.show', $meal->diet_plan_id).'#platillo-'.$meal->id.'-'.$dish->id)
            ->assertSessionHas('success');

        // Sustituir el aguacate por un personalizado (mismo lugar y platillo)
        $avocado = $this->row($meal, $this->avocado);
        $this->actingAs($user)->patch($this->base($meal).'/alimentos/'.$avocado->id.'/sustituir', [
            'custom_name' => 'Salsa verde',
            'grams' => '40',
            'custom_kcal' => '12',
            'custom_protein_g' => '0.5',
            'custom_carbs_g' => '2',
            'custom_lipids_g' => '0.2',
        ])->assertSessionHasNoErrors();

        $avocado->refresh();
        $this->assertNull($avocado->food_id);
        $this->assertSame('Salsa verde', $avocado->custom_name);
        $this->assertSame($dish->id, $avocado->diet_plan_dish_id);

        // Agregar un ingrediente del catálogo al platillo (2 porciones de cebolla = 100 g)
        $this->actingAs($user)->post($this->base($meal).'/alimentos', [
            'food_id' => $this->onion->id,
            'grams' => '100',
            'diet_plan_dish_id' => $dish->id,
        ])->assertSessionHasNoErrors();

        $added = $meal->items()->where('food_id', $this->onion->id)->sole();
        $this->assertSame($dish->id, $added->diet_plan_dish_id);
        $this->assertSame('https://example.com/tacos.jpg', $added->custom_image_url);
        $this->assertEquals(2.0, (float) $added->equivalents);

        // Cambiar la cantidad del pollo en porciones: 3 × 30 g = 90 g
        $chicken = $this->row($meal, $this->chicken);
        $this->actingAs($user)->patch($this->base($meal).'/alimentos/'.$chicken->id, ['grams' => '3', 'unit' => 'porciones'])
            ->assertSessionHasNoErrors();
        $this->assertEquals(90.0, (float) $chicken->fresh()->grams);
        $this->assertEquals(3.0, (float) $chicken->fresh()->equivalents);

        // Doble porción del platillo: gramos y aporte del personalizado ×2
        $this->actingAs($user)->patch($this->base($meal).'/platillos/'.$dish->id.'/porciones', ['factor' => '2'])
            ->assertSessionHasNoErrors();

        $this->assertEquals(180.0, (float) $chicken->fresh()->grams);
        $this->assertEquals(6.0, (float) $chicken->fresh()->equivalents);
        $this->assertEquals(80.0, (float) $avocado->fresh()->grams);
        $this->assertEquals(24.0, (float) $avocado->fresh()->custom_kcal);

        // Totales recalculados: pollo 6 × 40 + salsa 24 + cebolla 4 × 20 = 344 kcal
        $meal->load('items.food');
        $this->assertEqualsWithDelta(344.0, $meal->totals()['kcal'], 0.01);

        // La receta y el otro plan siguen igual
        $this->assertSame(3, $dish->items()->count());
        $this->assertEquals(120.0, (float) $dish->items()->where('food_id', $this->chicken->id)->value('grams'));
        $this->assertSame(3, $otherMeal->items()->count());
        $this->assertEquals(120.0, (float) $otherMeal->items()->where('food_id', $this->chicken->id)->value('grams'));
        $this->assertSame(1, $otherMeal->items()->where('food_id', $this->onion->id)->count());

        // Persiste al recargar: se agrupa y se marca como personalizado
        $this->actingAs($user)->get(route('diet-plans.show', $meal->diet_plan_id))->assertOk()
            ->assertSee('id="platillo-'.$meal->id.'-'.$dish->id.'"', false)
            ->assertSee('Personalizado para este paciente')
            ->assertSee('Salsa verde')
            ->assertSee('Agregar ingrediente')
            ->assertSee('Sustituir')
            ->assertSee('Quitar');

        // El plan sin cambios no se marca
        $this->actingAs($user)->get(route('diet-plans.show', $otherMeal->diet_plan_id))->assertOk()
            ->assertDontSee('Personalizado para este paciente');
    }

    public function test_substituting_with_a_catalog_food_clears_custom_values(): void
    {
        $user = $this->account();
        $meal = $this->mealOf($user);
        $dish = $this->dish();
        $this->addDish($user, $meal, $dish);

        $avocado = $this->row($meal, $this->avocado);

        $this->actingAs($user)->patch($this->base($meal).'/alimentos/'.$avocado->id.'/sustituir', [
            'custom_name' => 'Queso panela', 'grams' => '40', 'custom_kcal' => '100',
            'custom_protein_g' => '7', 'custom_carbs_g' => '1', 'custom_lipids_g' => '7',
        ]);

        $this->actingAs($user)->patch($this->base($meal).'/alimentos/'.$avocado->id.'/sustituir', [
            'food_id' => $this->chicken->id, 'grams' => '60',
        ])->assertSessionHasNoErrors();

        $avocado->refresh();
        $this->assertSame($this->chicken->id, $avocado->food_id);
        $this->assertNull($avocado->custom_name);
        $this->assertNull($avocado->custom_kcal);
        $this->assertEquals(2.0, (float) $avocado->equivalents);
        $this->assertSame($dish->id, $avocado->diet_plan_dish_id);
    }

    public function test_invalid_values_are_rejected_without_changes(): void
    {
        $user = $this->account();
        $meal = $this->mealOf($user);
        $dish = $this->dish();
        $this->addDish($user, $meal, $dish);
        $chicken = $this->row($meal, $this->chicken);

        // Sustitución vacía o con cantidad inválida
        $this->actingAs($user)->patch($this->base($meal).'/alimentos/'.$chicken->id.'/sustituir', ['grams' => '50'])
            ->assertSessionHasErrors('food_id');
        $this->actingAs($user)->patch($this->base($meal).'/alimentos/'.$chicken->id.'/sustituir', ['food_id' => $this->onion->id, 'grams' => '0'])
            ->assertSessionHasErrors('grams');
        $this->actingAs($user)->patch($this->base($meal).'/alimentos/'.$chicken->id, ['grams' => '-1'])
            ->assertSessionHasErrors('grams');
        $this->actingAs($user)->patch($this->base($meal).'/alimentos/'.$chicken->id, ['grams' => '9999', 'unit' => 'porciones'])
            ->assertSessionHasErrors('grams');

        // Porciones fuera de rango o que rebasan el límite de un ingrediente
        $this->actingAs($user)->patch($this->base($meal).'/platillos/'.$dish->id.'/porciones', ['factor' => '0'])
            ->assertSessionHasErrors('factor');
        $chicken->update(['grams' => 2000, 'equivalents' => 66.67]);
        $this->actingAs($user)->patch($this->base($meal).'/platillos/'.$dish->id.'/porciones', ['factor' => '6'])
            ->assertSessionHasErrors('factor');

        // Todo o nada: la cebolla no se escaló
        $this->assertEquals(50.0, (float) $this->row($meal, $this->onion)->grams);
        $this->assertSame($this->chicken->id, $chicken->fresh()->food_id);

        // Platillo que no está en esta comida
        $this->actingAs($user)->post($this->base($meal).'/alimentos', [
            'food_id' => $this->onion->id, 'grams' => '50', 'diet_plan_dish_id' => 999,
        ])->assertSessionHasErrors('diet_plan_dish_id');
        $this->actingAs($user)->patch($this->base($meal).'/platillos/999/porciones', ['factor' => '2'])
            ->assertNotFound();
    }

    public function test_other_accounts_cannot_customize_a_plan_and_duplicates_keep_customizations(): void
    {
        $owner = $this->account();
        $intruder = $this->account();
        $meal = $this->mealOf($owner);
        $dish = $this->dish();
        $this->addDish($owner, $meal, $dish);
        $chicken = $this->row($meal, $this->chicken);

        $this->actingAs($intruder)->patch($this->base($meal).'/alimentos/'.$chicken->id.'/sustituir', ['food_id' => $this->onion->id, 'grams' => '50'])
            ->assertNotFound();
        $this->actingAs($intruder)->patch($this->base($meal).'/platillos/'.$dish->id.'/porciones', ['factor' => '2'])
            ->assertNotFound();

        // {item} de otra comida → 404 (scopeBindings)
        $otherMeal = $this->mealOf($owner);
        $this->actingAs($owner)->patch($this->base($otherMeal).'/alimentos/'.$chicken->id.'/sustituir', ['food_id' => $this->onion->id, 'grams' => '50'])
            ->assertNotFound();

        $this->assertSame($this->chicken->id, $chicken->fresh()->food_id);

        // Personalizar y duplicar: la copia conserva la personalización y el platillo
        $this->actingAs($owner)->patch($this->base($meal).'/alimentos/'.$chicken->id.'/sustituir', ['food_id' => $this->avocado->id, 'grams' => '60'])
            ->assertSessionHasNoErrors();

        $plan = $meal->dietPlan;
        $this->actingAs($owner)->post(route('diet-plans.duplicate', $plan), [
            'patient_id' => $plan->patient_id,
            'name' => 'Copia',
            'starts_at' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $copy = DietPlan::where('name', 'Copia')->sole();
        $copiedItems = $copy->meals()->first()->items;
        $this->assertSame(2, $copiedItems->where('food_id', $this->avocado->id)->count());
        $this->assertSame(0, $copiedItems->where('food_id', $this->chicken->id)->count());
        $this->assertTrue($copiedItems->every(fn ($item) => $item->diet_plan_dish_id === $dish->id));
    }

    public function test_entries_group_dish_ingredients_and_keep_loose_foods(): void
    {
        $user = $this->account();
        $meal = $this->mealOf($user);
        $dish = $this->dish();

        $meal->items()->create(['food_id' => $this->onion->id, 'grams' => 50, 'equivalents' => 1]);
        $this->addDish($user, $meal, $dish);

        $entries = $meal->fresh()->entries();

        $this->assertCount(2, $entries);
        $this->assertNull($entries[0]['dish_id']);
        $this->assertSame($dish->id, $entries[1]['dish_id']);
        $this->assertCount(3, $entries[1]['items']);
    }
}
