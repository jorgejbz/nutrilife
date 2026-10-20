<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DietPlan;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * DemoDataSeeder: conserva los 3 usuarios base, no duplica nada y deja
 * datos coherentes para cada escenario.
 */
class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    private function snapshot(): array
    {
        return collect(['users', 'subscriptions', 'subscription_payments', 'patients', 'appointments', 'consultations', 'evaluations', 'payments', 'diet_plans', 'diet_plan_meals', 'diet_plan_items', 'foods'])
            ->mapWithKeys(fn ($table) => [$table => \DB::table($table)->count()])
            ->all();
    }

    public function test_it_keeps_existing_base_users_and_is_idempotent(): void
    {
        // Usuario base cargado antes por demo_usuarios_por_plan.sql
        $this->seed(SubscriptionPlanSeeder::class);
        $existing = User::factory()->create([
            'email' => 'pro@nutrilife.test', 'name' => 'Dr. Carlos Ibarra Ruiz', 'access_code' => '724902', 'role_id' => Role::NUTRITIONIST,
        ]);
        Subscription::create([
            'user_id' => $existing->id, 'subscription_plan_id' => SubscriptionPlan::where('name', 'Pro')->value('id'),
            'status' => 'activa', 'starts_at' => now()->subDays(10)->toDateString(), 'current_period_ends_at' => now()->addDays(20)->toDateString(),
        ]);
        $existingPatient = Patient::create(['user_id' => $existing->id, 'first_name' => 'Ana Lucía', 'last_name' => 'Gómez Prieto', 'email' => 'ana.gomez@pacientes-pro.test', 'active' => true]);

        $this->seed(DemoDataSeeder::class);
        $first = $this->snapshot();

        $this->seed(DemoDataSeeder::class);
        $this->assertSame($first, $this->snapshot(), 'La segunda ejecución no debe crear registros.');

        // El usuario y el paciente que ya existían siguen igual (mismo id)
        $this->assertSame($existing->id, User::where('email', 'pro@nutrilife.test')->value('id'));
        $this->assertSame(0, Appointment::where('patient_id', $existingPatient->id)->count(), 'No se tocan pacientes existentes.');

        foreach (['esencial', 'pro', 'consultorio'] as $base) {
            $user = User::where('email', "{$base}@nutrilife.test")->firstOrFail();
            $this->assertTrue($user->latestSubscription->grantsPanelAccess(), "{$base} debe tener paquete vigente");
        }
    }

    public function test_scenarios_and_permissions(): void
    {
        $this->seed(DemoDataSeeder::class);

        $esencial = User::where('email', 'esencial@nutrilife.test')->first();
        $pro = User::where('email', 'pro@nutrilife.test')->first();
        $consultorio = User::where('email', 'consultorio@nutrilife.test')->first();

        // Planes solo en cuentas que los incluyen
        $this->assertSame(0, DietPlan::where('user_id', $esencial->id)->count());
        $this->assertGreaterThan(0, DietPlan::where('user_id', $pro->id)->count());
        $this->assertFalse($esencial->hasFeature('diet_plans'));
        $this->assertTrue($pro->hasFeature('diet_plans'));

        // Activos e inactivos, citas pasadas y futuras
        foreach ([$esencial, $pro, $consultorio] as $user) {
            $this->assertGreaterThan(0, $user->patients()->where('active', false)->count());
            $this->assertGreaterThan(0, Appointment::where('user_id', $user->id)->where('appointment_at', '<', now())->count());
            $this->assertGreaterThan(0, Appointment::where('user_id', $user->id)->where('appointment_at', '>', now())->count());
        }

        // Aislamiento: ningún plan apunta a un paciente de otra cuenta
        $this->assertSame(0, DietPlan::join('patients', 'patients.id', '=', 'diet_plans.patient_id')
            ->whereColumn('patients.user_id', '!=', 'diet_plans.user_id')->count());
        $plan = DietPlan::where('user_id', $consultorio->id)->first();
        $this->actingAs($pro)->get("/planes-alimenticios/{$plan->id}")->assertNotFound();

        // Escenarios de cuenta
        $this->assertTrue(User::where('email', 'prueba@nutrilife.test')->first()->latestSubscription->grantsPanelAccess());
        $this->assertTrue(User::where('email', 'prueba-vencida@nutrilife.test')->first()->latestSubscription->trialHasExpired());
        $this->assertTrue(User::where('email', 'pendiente@nutrilife.test')->first()->isPendingActivation());
        $this->assertTrue(User::where('email', 'cancelada@nutrilife.test')->first()->hasFeature('diet_plans'));
        $this->assertFalse(User::where('email', 'vencida@nutrilife.test')->first()->latestSubscription->grantsPanelAccess());

        // Las cuentas ven datos y el dashboard responde
        $this->actingAs($pro)->get('/dashboard')->assertOk();
        $this->actingAs(User::where('email', 'prueba-vencida@nutrilife.test')->first())->get('/dashboard')->assertRedirect('/contratar');
    }
}
