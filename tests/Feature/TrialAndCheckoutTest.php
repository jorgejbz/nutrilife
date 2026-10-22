<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prueba gratuita: vencimiento exacto, bloqueo del panel, pantalla de
 * contratación y compra simulada.
 */
class TrialAndCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Esencial' => 249, 'Pro' => 399, 'Consultorio' => 599] as $name => $price) {
            SubscriptionPlan::create(['name' => $name, 'price' => $price, 'billing_period' => 'mensual', 'max_patients' => $name === 'Consultorio' ? null : 50, 'active' => true]);
        }
    }

    private function userInTrial(): User
    {
        $user = User::factory()->create();
        Subscription::startTrialFor($user);

        return $user->fresh();
    }

    public function test_trial_expires_exactly_n_days_after_its_real_start(): void
    {
        $this->travelTo(now()->setTime(15, 30));
        $user = $this->userInTrial();

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Te quedan 7 días de prueba');

        // 7 días después, un minuto antes de la hora de inicio: todavía entra
        $this->travelTo(now()->addDays(7)->subMinute());
        $this->actingAs($user->fresh())->get('/pacientes')->assertOk();

        // Se cumple la hora exacta: se bloquea el panel
        $this->travel(2)->minutes();
        $this->actingAs($user->fresh())->get('/pacientes')->assertRedirect('/contratar');
        $this->actingAs($user->fresh())->get('/dashboard')->assertRedirect('/contratar');

        $this->assertSame(Subscription::STATUS_EXPIRED, $user->fresh()->latestSubscription->status);
    }

    public function test_trial_length_comes_from_config(): void
    {
        config(['nutrilife.trial_days' => 3]);
        $user = $this->userInTrial();

        $this->travel(3)->days();
        $this->travel(1)->minutes();

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/contratar');
    }

    public function test_plans_screen_shows_real_packages_without_redirect_loop(): void
    {
        $user = $this->userInTrial();
        $this->travel(8)->days();

        $this->actingAs($user)->get('/contratar')
            ->assertOk()
            ->assertSee('Tu periodo de prueba terminó')
            ->assertSee('Esencial')->assertSee('$249', false)
            ->assertSee('Pro')->assertSee('$399', false)
            ->assertSee('Consultorio')->assertSee('$599', false)
            ->assertSee('Hasta 50 pacientes')
            ->assertSee('Pacientes sin límite');

        // Cerrar sesión siempre funciona
        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));
    }

    public function test_simulated_checkout_activates_the_package_and_records_the_charge(): void
    {
        $user = $this->userInTrial();
        $this->travel(8)->days();
        $pro = SubscriptionPlan::where('name', 'Pro')->first();

        $this->actingAs($user)
            ->post("/contratar/{$pro->id}", ['price' => 1, 'billing_period' => 'anual'])
            ->assertRedirect(route('dashboard.index'))
            ->assertSessionHas('success');

        $subscription = $user->fresh()->latestSubscription;
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertSame($pro->id, (int) $subscription->subscription_plan_id);
        $this->assertSame(now()->addMonthNoOverflow()->toDateString(), $subscription->current_period_ends_at->toDateString());

        $payment = $subscription->payments()->firstOrFail();
        $this->assertEquals(399.00, (float) $payment->amount, 'El precio sale de la BD, no de la petición.');
        $this->assertSame('Simulado', $payment->payment_method);
        $this->assertMatchesRegularExpression('/^SIM-'.now()->format('Ymd').'-\d{6}$/', $payment->internal_reference);
        $this->assertNull($payment->reference, 'Sin pasarela no se inventa una referencia externa.');

        // Ya no aparece la pantalla de contratación
        $this->actingAs($user->fresh())->get('/dashboard')->assertOk();
        $this->actingAs($user->fresh())->get('/planes-alimenticios')->assertOk();
    }

    public function test_cannot_buy_the_same_active_package_or_an_inactive_one(): void
    {
        $user = $this->userInTrial();
        $esencial = SubscriptionPlan::where('name', 'Esencial')->first();

        $this->actingAs($user)->post("/contratar/{$esencial->id}");
        $this->actingAs($user->fresh())->post("/contratar/{$esencial->id}")->assertSessionHasErrors('plan');

        $old = SubscriptionPlan::create(['name' => 'Antiguo', 'price' => 99, 'billing_period' => 'mensual', 'active' => false]);
        $this->actingAs($user->fresh())->post("/contratar/{$old->id}")->assertSessionHasErrors('plan');

        $this->assertSame(1, $user->subscriptions()->where('status', Subscription::STATUS_ACTIVE)->count());
    }

    public function test_changing_package_cancels_the_previous_one(): void
    {
        $user = $this->userInTrial();
        $esencial = SubscriptionPlan::where('name', 'Esencial')->first();
        $consultorio = SubscriptionPlan::where('name', 'Consultorio')->first();

        $this->actingAs($user)->post("/contratar/{$esencial->id}");
        $this->actingAs($user->fresh())->get('/contratar')->assertSee('Tu paquete actual')->assertSee('Cambiar a Consultorio');
        $this->actingAs($user->fresh())->post("/contratar/{$consultorio->id}");

        $this->assertSame('Consultorio', $user->fresh()->latestSubscription->plan->name);
        $this->assertSame(1, $user->subscriptions()->where('status', Subscription::STATUS_CANCELLED)->count());
    }

    public function test_paid_package_that_expired_also_goes_to_plans(): void
    {
        $user = User::factory()->create();
        Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => SubscriptionPlan::where('name', 'Pro')->value('id'),
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now()->subMonths(2)->toDateString(),
            'current_period_ends_at' => now()->subDay()->toDateString(),
        ])->payments()->create(['amount' => 399, 'paid_at' => now()->subMonths(2), 'payment_method' => 'Tarjeta', 'status' => 'pagado']);

        $this->actingAs($user)->get('/citas')->assertRedirect('/contratar');
        $this->actingAs($user)->get('/contratar')->assertOk()->assertSee('Tu suscripción terminó');
    }

    public function test_admin_is_never_sent_to_plans(): void
    {
        $admin = User::factory()->create(['role_id' => Role::ADMIN]);

        // El administrador entra a su propio panel, nunca a la contratación.
        $this->actingAs($admin)->get('/dashboard')->assertRedirect('/admin');
        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/contratar')->assertRedirect('/admin');
    }

    public function test_simulated_checkout_can_be_disabled(): void
    {
        config(['nutrilife.checkout.simulated_payments' => false]);
        $user = $this->userInTrial();
        $pro = SubscriptionPlan::where('name', 'Pro')->first();

        $this->actingAs($user)->get('/contratar')->assertSee('No disponible en línea');
        $this->actingAs($user)->post("/contratar/{$pro->id}")->assertSessionHasErrors('plan');
    }
}
