<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\DietPlan;
use App\Models\DietPlanDish;
use App\Models\DietPlanItem;
use App\Models\Evaluation;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\ClinicalCatalogSeeder;
use Database\Seeders\DietPlanDishSeeder;
use Database\Seeders\FoodSeeder;
use Database\Seeders\IncmnszCatalogSeeder;
use Database\Seeders\ProDemoSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    private function proUser(): User
    {
        $this->seed([SubscriptionPlanSeeder::class, ClinicalCatalogSeeder::class, FoodSeeder::class, IncmnszCatalogSeeder::class, DietPlanDishSeeder::class]);

        $user = User::factory()->create(['email' => ProDemoSeeder::EMAIL, 'created_at' => now()->subDays(60)]);
        $user->forceFill(['role_id' => Role::NUTRITIONIST])->save();

        Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => SubscriptionPlan::where('name', 'Pro')->value('id'),
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now()->subDays(5)->toDateString(),
            'current_period_ends_at' => now()->addDays(25)->toDateString(),
        ]);

        return $user->fresh();
    }

    public function test_loads_complete_data_only_for_the_pro_account_and_is_idempotent(): void
    {
        $user = $this->proUser();
        $other = User::factory()->create();

        $this->seed(ProDemoSeeder::class);

        $counts = fn () => [
            Patient::withTrashed()->count(), Appointment::count(), Consultation::count(),
            Evaluation::count(), Payment::count(), DietPlan::count(), DietPlanItem::count(), DietPlanDish::count(),
        ];
        $first = $counts();

        $this->seed(ProDemoSeeder::class);
        $this->assertSame($first, $counts(), 'La segunda corrida no duplica nada');

        // 10 pacientes + 1 dado de baja, todos de pro@
        $this->assertSame(11, Patient::withTrashed()->where('user_id', $user->id)->count());
        $this->assertSame(1, Patient::onlyTrashed()->where('user_id', $user->id)->count());
        $this->assertSame(0, Patient::withTrashed()->where('user_id', $other->id)->count());
        $this->assertTrue(Patient::where('user_id', $user->id)->where('active', false)->exists());

        // Agenda, consultas, evaluaciones y pagos con todos los estados
        $statuses = Appointment::where('user_id', $user->id)->pluck('status')->unique()->sort()->values()->all();
        $this->assertSame(['Cancelada', 'Completada', 'Confirmada', 'Pendiente'], $statuses);
        $this->assertSame(2, Appointment::where('user_id', $user->id)->whereDate('appointment_at', today())->count());
        $this->assertGreaterThan(10, Consultation::where('user_id', $user->id)->count());
        $this->assertGreaterThan(10, Evaluation::whereIn('patient_id', Patient::withTrashed()->where('user_id', $user->id)->pluck('id'))->count());
        $this->assertTrue(Payment::where('user_id', $user->id)->where('status', 'Pendiente')->exists());

        // Planes: activos y finalizados, con platillos y alimento personalizado
        $this->assertTrue(DietPlan::where('user_id', $user->id)->where('active', false)->exists());
        // Sin platillos propios: el de ejemplo (Ensalada de Nopales) se retiró.
        $this->assertSame(0, DietPlanDish::where('user_id', $user->id)->count());
        $this->assertSame(3, DietPlanItem::whereNotNull('diet_plan_dish_id')->distinct()->count('diet_plan_meal_id'));
        $this->assertTrue(DietPlanItem::where('custom_name', 'Barra de proteína comercial')->exists());
    }

    public function test_pro_account_pages_work_with_the_demo_data(): void
    {
        $user = $this->proUser();
        $this->seed(ProDemoSeeder::class);

        $patient = Patient::where('user_id', $user->id)->where('email', 'fernanda.lozano@pacientes-pro.test')->firstOrFail();
        $plan = DietPlan::where('patient_id', $patient->id)->where('active', true)->firstOrFail();

        foreach (['/dashboard', '/pacientes', "/pacientes/{$patient->id}", '/citas', '/consultas', '/pagos', '/progreso', "/progreso/{$patient->id}", '/planes-alimenticios', '/alimentos', '/platillos', '/perfil'] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }

        // El platillo con huevo en el plan de Fernanda (alergia severa) muestra el aviso
        $this->actingAs($user)->get("/planes-alimenticios/{$plan->id}")
            ->assertOk()
            ->assertSee('Tostadas de Aguacate con Huevo Pochado')
            ->assertSee('alergia severa');

        // El platillo de ejemplo «Ensalada de Nopales con Queso Panela» ya no se crea;
        // los platillos del sistema siguen en el catálogo.
        $this->actingAs($user)->get('/platillos')
            ->assertOk()
            ->assertDontSee('Ensalada de Nopales con Queso Panela');
    }

    public function test_does_nothing_if_the_pro_account_does_not_exist(): void
    {
        $this->seed(ProDemoSeeder::class);

        $this->assertSame(0, Patient::count());
    }
}
