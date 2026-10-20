<?php

namespace Tests\Feature;

use App\Models\DietPlan;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Planes alimenticios: quién entra según su plan y su suscripción.
 * Pro y Consultorio (pagados y vigentes) y la prueba gratuita vigente sí;
 * Esencial, prueba vencida, suscripción vencida o administrador no.
 */
class DietPlanAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Esencial' => 249, 'Pro' => 399, 'Consultorio' => 599] as $name => $price) {
            SubscriptionPlan::create(['name' => $name, 'price' => $price, 'billing_period' => 'mensual', 'active' => true]);
        }
    }

    private function nutritionist(string $plan, string $status, int $daysLeft = 15): User
    {
        $user = User::factory()->create();

        Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => SubscriptionPlan::where('name', $plan)->value('id'),
            'status' => $status,
            'starts_at' => now()->subDays(10)->toDateString(),
            'current_period_ends_at' => now()->addDays($daysLeft)->toDateString(),
        ]);

        return $user;
    }

    public static function allowedAccounts(): array
    {
        return [
            'Pro activa' => ['Pro', Subscription::STATUS_ACTIVE, 15],
            'Consultorio activa' => ['Consultorio', Subscription::STATUS_ACTIVE, 15],
            'Pro cancelada dentro del periodo pagado' => ['Pro', Subscription::STATUS_CANCELLED, 5],
            'Prueba gratuita vigente' => ['Esencial', Subscription::STATUS_TRIAL, 3],
            'Prueba en su último día' => ['Esencial', Subscription::STATUS_TRIAL, 0],
        ];
    }

    /** Cuentas con panel vigente pero sin la función (403 en la sección). */
    public static function deniedAccounts(): array
    {
        return [
            'Esencial activa' => ['Esencial', Subscription::STATUS_ACTIVE, 15],
            'Esencial cancelada dentro del periodo' => ['Esencial', Subscription::STATUS_CANCELLED, 5],
        ];
    }

    /**
     * Cuentas sin prueba ni paquete vigente: desde la actualización 3.1 no
     * entran a NINGUNA ruta del panel; se van a la pantalla de contratación.
     */
    public static function expiredAccounts(): array
    {
        return [
            'Prueba vencida' => ['Esencial', Subscription::STATUS_TRIAL, -1],
            'Pro vencida' => ['Pro', Subscription::STATUS_EXPIRED, -3],
            'Pro activa con periodo ya terminado' => ['Pro', Subscription::STATUS_ACTIVE, -1],
            'Consultorio cancelada y periodo terminado' => ['Consultorio', Subscription::STATUS_CANCELLED, -2],
        ];
    }

    #[DataProvider('expiredAccounts')]
    public function test_expired_accounts_are_sent_to_the_plans_screen(string $plan, string $status, int $daysLeft): void
    {
        $user = $this->nutritionist($plan, $status, $daysLeft);

        $this->actingAs($user)->get('/planes-alimenticios')->assertRedirect('/contratar');
        $this->actingAs($user)->post('/planes-alimenticios', [])->assertRedirect('/contratar');
        $this->actingAs($user)->getJson('/planes-alimenticios')->assertStatus(402);
        $this->assertDatabaseCount('diet_plans', 0);
    }

    #[DataProvider('allowedAccounts')]
    public function test_allowed_accounts_can_open_the_section(string $plan, string $status, int $daysLeft): void
    {
        $user = $this->nutritionist($plan, $status, $daysLeft);

        $this->actingAs($user)->get('/planes-alimenticios')->assertOk()->assertSee('Planes alimenticios');
        $this->actingAs($user)->get('/planes-alimenticios/crear')->assertOk();

        // El menú lateral muestra la opción
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('planes-alimenticios', false);
    }

    #[DataProvider('deniedAccounts')]
    public function test_other_accounts_are_blocked_on_every_route(string $plan, string $status, int $daysLeft): void
    {
        $user = $this->nutritionist($plan, $status, $daysLeft);
        $patient = Patient::create(['user_id' => $user->id, 'first_name' => 'Ana', 'last_name' => 'Ruiz', 'email' => 'ana@test.mx', 'active' => true]);

        // Un plan propio creado cuando sí tenía acceso (p. ej. bajó de Pro a Esencial)
        $dietPlan = DietPlan::create([
            'user_id' => $user->id, 'patient_id' => $patient->id, 'name' => 'Plan previo',
            'target_kcal' => 1800, 'target_protein_g' => 90, 'target_carbs_g' => 200, 'target_lipids_g' => 60,
            'starts_at' => now()->toDateString(), 'active' => true,
        ]);

        $this->actingAs($user)->get('/planes-alimenticios')->assertForbidden()->assertSee('Disponible en Pro y Consultorio');
        $this->actingAs($user)->get('/planes-alimenticios/crear')->assertForbidden();
        $this->actingAs($user)->get("/planes-alimenticios/{$dietPlan->id}")->assertForbidden();
        $this->actingAs($user)->getJson('/planes-alimenticios')->assertForbidden()->assertJsonStructure(['message']);

        // Peticiones manipuladas (sin pasar por la interfaz)
        $this->actingAs($user)->post('/planes-alimenticios', [
            'patient_id' => $patient->id, 'name' => 'Intento', 'starts_at' => now()->toDateString(),
            'target_kcal' => 1500, 'target_protein_g' => 80, 'target_carbs_g' => 150, 'target_lipids_g' => 50,
        ])->assertForbidden();
        $this->actingAs($user)->put("/planes-alimenticios/{$dietPlan->id}", ['name' => 'X'])->assertForbidden();
        $this->actingAs($user)->delete("/planes-alimenticios/{$dietPlan->id}")->assertForbidden();
        $this->actingAs($user)->post("/planes-alimenticios/{$dietPlan->id}/comidas", ['meal_name' => 'Cena'])->assertForbidden();

        $this->assertDatabaseCount('diet_plans', 1);
        $this->assertDatabaseCount('diet_plan_meals', 0);
        $this->assertNotSoftDeleted($dietPlan);

        // El menú no muestra la opción ni la ficha del paciente el bloque
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertDontSee('planes-alimenticios', false);
        $this->actingAs($user)->get("/pacientes/{$patient->id}")->assertOk()->assertDontSee('Planes alimenticios');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/planes-alimenticios')->assertRedirect('/login');
        $this->post('/planes-alimenticios')->assertRedirect('/login');
    }

    public function test_admin_has_no_access(): void
    {
        $admin = User::factory()->create(['role_id' => Role::ADMIN]);

        $this->actingAs($admin)->get('/planes-alimenticios')->assertForbidden();
    }

    public function test_a_plan_change_is_reflected_immediately(): void
    {
        $user = $this->nutritionist('Esencial', Subscription::STATUS_ACTIVE);
        $this->actingAs($user)->get('/planes-alimenticios')->assertForbidden();

        // Compra Pro: nueva suscripción (la más reciente es la que cuenta)
        Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => SubscriptionPlan::where('name', 'Pro')->value('id'),
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now()->toDateString(),
            'current_period_ends_at' => now()->addMonth()->toDateString(),
        ]);

        $this->actingAs($user->fresh())->get('/planes-alimenticios')->assertOk();

        // Se vence
        Subscription::where('user_id', $user->id)->latest('id')->first()
            ->update(['status' => Subscription::STATUS_EXPIRED, 'current_period_ends_at' => now()->subDay()->toDateString()]);

        // Vencido: ya no entra al panel, va a contratar
        $this->actingAs($user->fresh())->get('/planes-alimenticios')->assertRedirect('/contratar');
    }

    public function test_new_registration_starts_trial_with_access_after_activation(): void
    {
        $this->post('/register', [
            'name' => 'Nueva Nutrióloga',
            'email' => 'nueva@nutrilife.test',
            'professional_license' => '7654321',
            'degree_number' => 'LN-2021-777',
            'password' => 'password-segura-1',
            'password_confirmation' => 'password-segura-1',
        ]);

        $this->assertGuest();

        $this->travel((int) config('nutrilife.account_activation.delay_minutes') + 1)->minutes();

        $this->post('/login', ['email' => 'nueva@nutrilife.test', 'password' => 'password-segura-1']);
        $this->assertAuthenticated();
        $this->get('/planes-alimenticios')->assertOk();
    }
}
