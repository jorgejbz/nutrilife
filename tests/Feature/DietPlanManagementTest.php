<?php

namespace Tests\Feature;

use App\Models\DietPlan;
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
 * Operaciones de Planes alimenticios y aislamiento de datos entre cuentas
 * (cada nutriólogo/consultorio solo ve y modifica lo suyo).
 */
class DietPlanManagementTest extends TestCase
{
    use RefreshDatabase;

    private Food $oats;

    private Food $egg;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Esencial' => 249, 'Pro' => 399, 'Consultorio' => 599] as $name => $price) {
            SubscriptionPlan::create(['name' => $name, 'price' => $price, 'billing_period' => 'mensual', 'active' => true]);
        }

        $cereals = FoodGroup::create(['name' => 'Cereales']);
        $proteins = FoodGroup::create(['name' => 'Proteínas']);

        // Valores por porción (lo que guarda el catálogo foods)
        $this->oats = Food::create(['food_group_id' => $cereals->id, 'name' => 'Avena', 'portion_grams' => 30, 'portion_description' => '1/3 taza', 'kcal' => 117, 'protein_g' => 5, 'lipids_g' => 2, 'carbs_g' => 20, 'active' => true]);
        $this->egg = Food::create(['food_group_id' => $proteins->id, 'name' => 'Huevo', 'portion_grams' => 50, 'portion_description' => '1 pieza', 'kcal' => 70, 'protein_g' => 6, 'lipids_g' => 5, 'carbs_g' => 0.5, 'active' => true]);
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

    private function patientOf(User $user, array $attributes = []): Patient
    {
        return Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Paciente',
            'last_name' => $user->id,
            'email' => 'p'.$user->id.'-'.uniqid().'@test.mx',
            'active' => true,
            ...$attributes,
        ]);
    }

    private function planOf(User $user, ?Patient $patient = null): DietPlan
    {
        return DietPlan::create([
            'user_id' => $user->id,
            'patient_id' => ($patient ?? $this->patientOf($user))->id,
            'name' => 'Plan de '.$user->name,
            'target_kcal' => 1800, 'target_protein_g' => 100, 'target_carbs_g' => 200, 'target_lipids_g' => 60,
            'starts_at' => now()->toDateString(),
            'active' => true,
        ]);
    }

    private function validPlanData(Patient $patient, array $overrides = []): array
    {
        return [
            'patient_id' => $patient->id,
            'name' => 'Déficit 1,600 kcal',
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addMonth()->toDateString(),
            'target_kcal' => '1600',
            'target_protein_g' => '100',
            'target_carbs_g' => '180',
            'target_lipids_g' => '55',
            ...$overrides,
        ];
    }

    /* ------------------------------------------------------------------
     | Operaciones
     * ------------------------------------------------------------------ */

    public function test_full_flow_create_build_edit_and_delete(): void
    {
        $user = $this->account('Pro');
        $patient = $this->patientOf($user);

        // Crear
        $response = $this->actingAs($user)->post('/planes-alimenticios', $this->validPlanData($patient));
        $plan = DietPlan::firstOrFail();
        $response->assertRedirect("/planes-alimenticios/{$plan->id}");
        $this->assertSame($user->id, $plan->user_id);
        $this->assertTrue($plan->active);

        // Comida
        $this->actingAs($user)->post("/planes-alimenticios/{$plan->id}/comidas", ['meal_name' => 'Desayuno'])->assertRedirect();
        $meal = $plan->meals()->firstOrFail();
        $this->assertSame(1, $meal->meal_order);

        // Alimento: 60 g de avena = 2 porciones de 30 g
        $this->actingAs($user)->post("/planes-alimenticios/{$plan->id}/comidas/{$meal->id}/alimentos", [
            'food_id' => $this->oats->id, 'grams' => '60',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $item = $meal->items()->firstOrFail();
        $this->assertEquals(2.00, (float) $item->equivalents);
        $this->assertEquals(234.0, round($plan->fresh()->totals()['kcal'], 2));

        // Cambiar gramos → recalcula equivalentes
        $this->actingAs($user)->patch("/planes-alimenticios/{$plan->id}/comidas/{$meal->id}/alimentos/{$item->id}", ['grams' => '45'])
            ->assertSessionHasNoErrors();
        $this->assertEquals(1.5, (float) $item->fresh()->equivalents);

        // La vista del constructor muestra totales y el alimento
        $this->actingAs($user)->get("/planes-alimenticios/{$plan->id}")
            ->assertOk()->assertSee('Avena')->assertSee('Desayuno')->assertSee('176');

        // Editar: finalizar
        $this->actingAs($user)->put("/planes-alimenticios/{$plan->id}", [
            ...$this->validPlanData($patient, ['name' => 'Plan ajustado']),
            'active' => '0',
        ])->assertRedirect("/planes-alimenticios/{$plan->id}");
        $this->assertFalse($plan->fresh()->active);
        $this->assertSame('Plan ajustado', $plan->fresh()->name);

        // Quitar alimento y comida
        $this->actingAs($user)->delete("/planes-alimenticios/{$plan->id}/comidas/{$meal->id}/alimentos/{$item->id}")->assertRedirect();
        $this->assertDatabaseMissing('diet_plan_items', ['id' => $item->id]);
        $this->actingAs($user)->delete("/planes-alimenticios/{$plan->id}/comidas/{$meal->id}")->assertRedirect();
        $this->assertDatabaseMissing('diet_plan_meals', ['id' => $meal->id]);

        // Eliminar plan (soft delete: diet_plans.deleted_at)
        $this->actingAs($user)->delete("/planes-alimenticios/{$plan->id}")->assertRedirect('/planes-alimenticios');
        $this->assertSoftDeleted($plan);
        $this->actingAs($user)->get("/planes-alimenticios/{$plan->id}")->assertNotFound();
    }

    public function test_patient_page_lists_the_plans_and_prefills_create(): void
    {
        $user = $this->account('Consultorio');
        $patient = $this->patientOf($user);
        $this->planOf($user, $patient);

        $this->actingAs($user)->get("/pacientes/{$patient->id}")->assertOk()->assertSee('Plan de '.$user->name);
        $this->actingAs($user)->get("/planes-alimenticios/crear?paciente={$patient->id}")
            ->assertOk()->assertSee('value="'.$patient->id.'" selected', false);
    }

    public function test_validation_respects_the_database_limits(): void
    {
        $user = $this->account();
        $patient = $this->patientOf($user);

        $this->actingAs($user)->post('/planes-alimenticios', [])
            ->assertSessionHasErrors(['patient_id', 'name', 'starts_at', 'target_kcal', 'target_protein_g', 'target_carbs_g', 'target_lipids_g']);

        $this->actingAs($user)->post('/planes-alimenticios', $this->validPlanData($patient, [
            'target_kcal' => '100000',          // DECIMAL(7,2)
            'target_protein_g' => '-1',
            'target_carbs_g' => '10.555',       // 2 decimales
            'ends_at' => now()->subDay()->toDateString(),
            'name' => str_repeat('a', 256),
        ]))->assertSessionHasErrors(['target_kcal', 'target_protein_g', 'target_carbs_g', 'ends_at', 'name']);

        // Paciente inactivo no se puede elegir
        $inactive = $this->patientOf($user, ['active' => false]);
        $this->actingAs($user)->post('/planes-alimenticios', $this->validPlanData($inactive))->assertSessionHasErrors('patient_id');

        $plan = $this->planOf($user, $patient);
        $meal = $plan->meals()->create(['meal_name' => 'Comida', 'meal_order' => 1]);

        // equivalents DECIMAL(4,2): 5,000 g de huevo = 100 porciones > 99.99
        $this->actingAs($user)->post("/planes-alimenticios/{$plan->id}/comidas/{$meal->id}/alimentos", [
            'food_id' => $this->egg->id, 'grams' => '5000',
        ])->assertSessionHasErrors('grams');

        // Alimento inactivo o inexistente
        $this->oats->update(['active' => false]);
        $this->actingAs($user)->post("/planes-alimenticios/{$plan->id}/comidas/{$meal->id}/alimentos", ['food_id' => $this->oats->id, 'grams' => 30])
            ->assertSessionHasErrors('food_id');
        $this->actingAs($user)->post("/planes-alimenticios/{$plan->id}/comidas/{$meal->id}/alimentos", ['food_id' => 9999, 'grams' => 30])
            ->assertSessionHasErrors('food_id');

        $this->actingAs($user)->post("/planes-alimenticios/{$plan->id}/comidas", ['meal_name' => str_repeat('x', 101)])
            ->assertSessionHasErrors('meal_name');

        $this->assertDatabaseCount('diet_plan_items', 0);
    }

    public function test_patient_is_not_changed_on_update(): void
    {
        $user = $this->account();
        $patient = $this->patientOf($user);
        $other = $this->patientOf($user);
        $plan = $this->planOf($user, $patient);

        $this->actingAs($user)->put("/planes-alimenticios/{$plan->id}", [
            ...$this->validPlanData($other),
            'active' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame($patient->id, $plan->fresh()->patient_id);
    }

    /* ------------------------------------------------------------------
     | Aislamiento entre cuentas (Pro ↔ Consultorio ↔ Consultorio)
     * ------------------------------------------------------------------ */

    public function test_accounts_cannot_see_or_touch_each_others_plans(): void
    {
        $clinicA = $this->account('Consultorio');
        $clinicB = $this->account('Consultorio');

        $planA = $this->planOf($clinicA);
        $mealA = $planA->meals()->create(['meal_name' => 'Cena', 'meal_order' => 1]);
        $itemA = $mealA->items()->create(['food_id' => $this->egg->id, 'grams' => 50, 'equivalents' => 1]);
        $planB = $this->planOf($clinicB);

        // Listado: solo los suyos
        $this->actingAs($clinicB)->get('/planes-alimenticios')
            ->assertOk()->assertSee($planB->name)->assertDontSee($planA->name);

        // Filtro por paciente ajeno se ignora
        $this->actingAs($clinicB)->get("/planes-alimenticios?paciente={$planA->patient_id}")
            ->assertOk()->assertDontSee($planA->name);

        // Ver, editar, actualizar, borrar el plan ajeno → 404 (no revela que existe)
        $this->actingAs($clinicB)->get("/planes-alimenticios/{$planA->id}")->assertNotFound();
        $this->actingAs($clinicB)->get("/planes-alimenticios/{$planA->id}/editar")->assertNotFound();
        $this->actingAs($clinicB)->put("/planes-alimenticios/{$planA->id}", ['name' => 'Hackeado'])->assertNotFound();
        $this->actingAs($clinicB)->delete("/planes-alimenticios/{$planA->id}")->assertNotFound();
        $this->actingAs($clinicB)->post("/planes-alimenticios/{$planA->id}/comidas", ['meal_name' => 'X'])->assertNotFound();
        $this->actingAs($clinicB)->delete("/planes-alimenticios/{$planA->id}/comidas/{$mealA->id}")->assertNotFound();
        $this->actingAs($clinicB)->delete("/planes-alimenticios/{$planA->id}/comidas/{$mealA->id}/alimentos/{$itemA->id}")->assertNotFound();

        // Comida/alimento ajeno colgado de un plan propio → 404 (scopeBindings)
        $this->actingAs($clinicB)->delete("/planes-alimenticios/{$planB->id}/comidas/{$mealA->id}")->assertNotFound();
        $this->actingAs($clinicB)->post("/planes-alimenticios/{$planB->id}/comidas/{$mealA->id}/alimentos", ['food_id' => $this->egg->id, 'grams' => 50])->assertNotFound();
        $mealB = $planB->meals()->create(['meal_name' => 'Comida', 'meal_order' => 1]);
        $this->actingAs($clinicB)->patch("/planes-alimenticios/{$planB->id}/comidas/{$mealB->id}/alimentos/{$itemA->id}", ['grams' => 1])->assertNotFound();

        // Crear un plan para el paciente de otra cuenta
        $this->actingAs($clinicB)->post('/planes-alimenticios', $this->validPlanData($planA->patient))
            ->assertSessionHasErrors('patient_id');

        // Nada cambió en la cuenta A
        $this->assertSame('Plan de '.$clinicA->name, $planA->fresh()->name);
        $this->assertNotSoftDeleted($planA);
        $this->assertDatabaseHas('diet_plan_meals', ['id' => $mealA->id]);
        $this->assertEquals(50, (float) $itemA->fresh()->grams);
        $this->assertSame(1, DietPlan::where('user_id', $clinicB->id)->count());

        // La ficha del paciente ajeno sigue protegida
        $this->actingAs($clinicB)->get("/pacientes/{$planA->patient_id}")->assertForbidden();
    }

    /* ------------------------------------------------------------------
     | Avisos clínicos (food_allergens, food_pathology_restrictions)
     * ------------------------------------------------------------------ */

    public function test_builder_warns_about_patient_allergies(): void
    {
        $user = $this->account();
        $patient = $this->patientOf($user);

        $allergen = \App\Models\Allergen::create(['name' => 'Huevo']);
        $patient->allergens()->attach($allergen->id, ['severity' => 'severa']);
        $this->egg->allergens()->attach($allergen->id);

        $plan = $this->planOf($user, $patient);

        $this->actingAs($user)->get("/planes-alimenticios/{$plan->id}")
            ->assertOk()->assertSee('Contiene Huevo (alergia severa)');
    }

    public function test_totals_are_calculated_from_the_catalog(): void
    {
        $user = $this->account();
        $plan = $this->planOf($user);

        $breakfast = $plan->meals()->create(['meal_name' => 'Desayuno', 'meal_order' => 1]);
        $breakfast->items()->create(['food_id' => $this->oats->id, 'grams' => 30, 'equivalents' => 1]);   // 117 kcal
        $breakfast->items()->create(['food_id' => $this->egg->id, 'grams' => 100, 'equivalents' => 2]);   // 140 kcal

        $totals = $plan->fresh()->load('meals.items.food')->totals();

        $this->assertEqualsWithDelta(257, $totals['kcal'], 0.001);
        $this->assertEqualsWithDelta(17, $totals['protein_g'], 0.001);
        $this->assertEqualsWithDelta(21, $totals['carbs_g'], 0.001);
        $this->assertEqualsWithDelta(12, $totals['lipids_g'], 0.001);
        $this->assertEqualsWithDelta(14.3, $plan->fresh()->progress()['kcal'], 0.1);
    }

    /* ------------------------------------------------------------------
     | Actualización 3.1: objetivo, indicaciones, horarios, duplicar, buscar
     * ------------------------------------------------------------------ */

    public function test_goal_instructions_and_meal_times_are_saved_and_shown(): void
    {
        $user = $this->account();
        $patient = $this->patientOf($user);

        $this->actingAs($user)->post('/planes-alimenticios', $this->validPlanData($patient, [
            'goal' => 'Bajar 4 kg en 2 meses',
            'instructions' => "Tomar 2 L de agua\nCenar temprano",
        ]))->assertSessionHasNoErrors();

        $plan = DietPlan::firstOrFail();
        $this->assertSame('Bajar 4 kg en 2 meses', $plan->goal);

        $this->actingAs($user)->post("/planes-alimenticios/{$plan->id}/comidas", ['meal_name' => 'Desayuno', 'meal_time' => '07:30'])
            ->assertSessionHasNoErrors();
        $meal = $plan->meals()->firstOrFail();
        $this->assertSame('07:30', $meal->timeLabel());

        $this->actingAs($user)->patch("/planes-alimenticios/{$plan->id}/comidas/{$meal->id}", [
            'meal_name' => 'Desayuno ligero', 'meal_time' => '08:00', 'notes' => 'Sin azúcar',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Sin azúcar', $meal->fresh()->notes);

        $this->actingAs($user)->patch("/planes-alimenticios/{$plan->id}/comidas/{$meal->id}", ['meal_name' => 'X', 'meal_time' => '25:99'])
            ->assertSessionHasErrors('meal_time');

        $this->actingAs($user)->get("/planes-alimenticios/{$plan->id}")
            ->assertOk()
            ->assertSee('Bajar 4 kg en 2 meses')
            ->assertSee('Cenar temprano')
            ->assertSee('08:00')
            ->assertSee('Sin azúcar');
    }

    public function test_plan_can_be_duplicated_for_another_own_patient_only(): void
    {
        $user = $this->account('Consultorio');
        $plan = $this->planOf($user);
        $meal = $plan->meals()->create(['meal_name' => 'Comida', 'meal_order' => 1, 'meal_time' => '14:00']);
        $meal->items()->create(['food_id' => $this->egg->id, 'grams' => 100, 'equivalents' => 2]);
        $other = $this->patientOf($user);

        $this->actingAs($user)->post("/planes-alimenticios/{$plan->id}/duplicar", [
            'patient_id' => $other->id, 'name' => 'Copia para otro', 'starts_at' => now()->toDateString(),
        ])->assertRedirect();

        $copy = DietPlan::where('name', 'Copia para otro')->firstOrFail();
        $this->assertSame($other->id, $copy->patient_id);
        $this->assertSame($user->id, $copy->user_id);
        $this->assertSame(1, $copy->meals()->count());
        $this->assertSame(1, $copy->meals()->first()->items()->count());
        $this->assertTrue($plan->fresh()->active, 'El original no cambia.');

        // Mismo paciente + finalizar el original
        $this->actingAs($user)->post("/planes-alimenticios/{$plan->id}/duplicar", [
            'patient_id' => $plan->patient_id, 'name' => 'Versión 2', 'starts_at' => now()->toDateString(), 'finish_original' => '1',
        ]);
        $this->assertFalse($plan->fresh()->active);

        // Paciente de otra cuenta: no se permite
        $stranger = $this->account('Consultorio');
        $foreignPatient = $this->patientOf($stranger);
        $this->actingAs($user)->post("/planes-alimenticios/{$plan->id}/duplicar", [
            'patient_id' => $foreignPatient->id, 'name' => 'Intento', 'starts_at' => now()->toDateString(),
        ])->assertSessionHasErrors('patient_id');

        // Plan de otra cuenta: 404
        $this->actingAs($stranger)->post("/planes-alimenticios/{$plan->id}/duplicar", [
            'patient_id' => $foreignPatient->id, 'name' => 'Robo', 'starts_at' => now()->toDateString(),
        ])->assertNotFound();

        $this->assertSame(0, DietPlan::whereIn('name', ['Intento', 'Robo'])->count());
    }

    public function test_index_search_filters_by_plan_goal_or_patient(): void
    {
        $user = $this->account();
        $ana = $this->patientOf($user, ['first_name' => 'Ana']);
        $luis = $this->patientOf($user, ['first_name' => 'Luis']);
        $this->planOf($user, $ana)->update(['name' => 'Plan deportivo', 'goal' => 'Ganar músculo']);
        $this->planOf($user, $luis)->update(['name' => 'Plan renal']);

        $this->actingAs($user)->get('/planes-alimenticios?q=renal')->assertSee('Plan renal')->assertDontSee('Plan deportivo');
        $this->actingAs($user)->get('/planes-alimenticios?q=músculo')->assertSee('Plan deportivo')->assertDontSee('Plan renal');
        $this->actingAs($user)->get('/planes-alimenticios?q=Luis')->assertSee('Plan renal')->assertDontSee('Plan deportivo');
    }
}
