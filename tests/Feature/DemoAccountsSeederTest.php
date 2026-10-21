<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DietPlan;
use App\Models\DietPlanDish;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\ClinicalCatalogSeeder;
use Database\Seeders\DemoAccountsSeeder;
use Database\Seeders\DietPlanDishSeeder;
use Database\Seeders\FoodSeeder;
use Database\Seeders\IncmnszCatalogSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * DemoAccountsSeeder: cada cuenta recibe datos según lo que permite su
 * paquete (Esencial sin planes), Manuel recibe su paquete Pro y nada se
 * duplica al repetir.
 */
class DemoAccountsSeederTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $email, ?string $plan, string $status = Subscription::STATUS_ACTIVE): User
    {
        $user = User::factory()->create(['email' => $email, 'created_at' => now()->subDays(60)]);
        $user->forceFill(['role_id' => Role::NUTRITIONIST])->save();

        if ($plan) {
            Subscription::create([
                'user_id' => $user->id,
                'subscription_plan_id' => SubscriptionPlan::where('name', $plan)->value('id'),
                'status' => $status,
                'starts_at' => now()->subDays($status === Subscription::STATUS_TRIAL ? 20 : 5)->toDateString(),
                'current_period_ends_at' => now()->addDays($status === Subscription::STATUS_TRIAL ? -13 : 25)->toDateString(),
            ]);
        }

        return $user->fresh();
    }

    public function test_each_account_gets_data_allowed_by_its_package(): void
    {
        $this->seed([SubscriptionPlanSeeder::class, ClinicalCatalogSeeder::class, FoodSeeder::class, IncmnszCatalogSeeder::class, DietPlanDishSeeder::class]);

        $consultorio = $this->account('consultorio@nutrilife.test', 'Consultorio');
        $esencial = $this->account('esencial@nutrilife.test', 'Esencial');
        $manuel = $this->account('josemanuelgarcializaola2212@gmail.com', null);
        $jorge = $this->account('jorgejb234@gmail.com', 'Esencial', Subscription::STATUS_TRIAL);

        $this->seed(DemoAccountsSeeder::class);
        $snapshot = [Patient::withTrashed()->count(), Appointment::count(), DietPlan::count(), DietPlanDish::count()];
        $this->seed(DemoAccountsSeeder::class);
        $this->assertSame($snapshot, [Patient::withTrashed()->count(), Appointment::count(), DietPlan::count(), DietPlanDish::count()], 'No duplica');

        // Manuel: paquete Pro activo con su cobro
        $latest = $manuel->subscriptions()->latest('id')->first();
        $this->assertSame('Pro', $latest->plan->name);
        $this->assertTrue($latest->hasPaidAccess());
        $this->assertTrue($manuel->fresh()->hasFeature('diet_plans'));

        foreach ([$consultorio, $manuel] as $user) {
            $this->assertTrue(DietPlan::where('user_id', $user->id)->where('active', true)->exists());
            $this->assertSame(2, DietPlanDish::where('user_id', $user->id)->count());
            $this->assertSame(1, Patient::onlyTrashed()->where('user_id', $user->id)->count());
            $this->assertGreaterThanOrEqual(1, Appointment::where('user_id', $user->id)->whereDate('appointment_at', today())->count());
        }

        // Esencial y la prueba vencida: sin planes ni platillos propios
        foreach ([$esencial, $jorge] as $user) {
            $this->assertTrue(Patient::where('user_id', $user->id)->exists());
            $this->assertSame(0, DietPlan::where('user_id', $user->id)->count());
            $this->assertSame(0, DietPlanDish::where('user_id', $user->id)->count());
            $this->assertSame(1, Patient::onlyTrashed()->where('user_id', $user->id)->count());
        }

        // La prueba de jorgejb sigue vencida
        $this->assertFalse($jorge->fresh()->subscriptions()->latest('id')->first()->hasPaidAccess());

        // Cada paciente activo tiene una cita a futuro
        $this->assertSame(0, Patient::where('active', true)
            ->whereIn('user_id', [$consultorio->id, $esencial->id, $manuel->id, $jorge->id])
            ->whereDoesntHave('appointments', fn ($q) => $q->where('appointment_at', '>', now())->whereIn('status', ['Pendiente', 'Confirmada']))
            ->count());

        // Las páginas principales funcionan con los datos
        foreach ([$consultorio, $manuel] as $user) {
            $plan = DietPlan::where('user_id', $user->id)->where('active', true)->firstOrFail();
            foreach (['/dashboard', '/pacientes', '/citas', '/consultas', '/pagos', '/progreso', '/planes-alimenticios', "/planes-alimenticios/{$plan->id}", '/platillos?origen=propios'] as $url) {
                $this->actingAs($user)->get($url)->assertOk();
            }
        }

        foreach (['/dashboard', '/pacientes', '/citas', '/consultas', '/pagos', '/progreso'] as $url) {
            $this->actingAs($esencial)->get($url)->assertOk();
        }
        $this->actingAs($esencial)->get('/planes-alimenticios')->assertForbidden();
    }
}
