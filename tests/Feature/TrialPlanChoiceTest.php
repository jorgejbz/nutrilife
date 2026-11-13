<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La prueba gratuita es del paquete que el nutriólogo elige (Esencial o Pro):
 * se elige al registrarse, se puede cambiar mientras dure y da solo las
 * funciones de ese paquete.
 */
class TrialPlanChoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Esencial' => [249, 25], 'Pro' => [399, 100], 'Consultorio' => [599, null]] as $name => [$price, $max]) {
            SubscriptionPlan::create(['name' => $name, 'price' => $price, 'billing_period' => 'mensual', 'max_patients' => $max, 'active' => true]);
        }
    }

    private function register(?string $trialPlan): User
    {
        $data = [
            'name' => 'Nueva Nutrióloga',
            'email' => 'nueva@nutrilife.test',
            'professional_license' => '7654321',
            'degree_number' => 'LN-2021-777',
            'password' => 'password-segura-1',
            'password_confirmation' => 'password-segura-1',
        ];

        if ($trialPlan !== null) {
            $data['trial_plan'] = $trialPlan;
        }

        $this->post('/register', $data);
        $this->travel((int) config('nutrilife.account_activation.delay_minutes') + 1)->minutes();
        $this->post('/login', ['email' => 'nueva@nutrilife.test', 'password' => 'password-segura-1']);
        $this->assertAuthenticated();

        return User::where('email', 'nueva@nutrilife.test')->firstOrFail();
    }

    private function trialUser(string $plan, int $daysAgo = 2): User
    {
        $user = User::factory()->create();
        $start = now()->subDays($daysAgo);

        Subscription::forceCreate([
            'user_id' => $user->id,
            'subscription_plan_id' => SubscriptionPlan::where('name', $plan)->value('id'),
            'status' => Subscription::STATUS_TRIAL,
            'starts_at' => $start->toDateString(),
            'current_period_ends_at' => $start->copy()->addDays(Subscription::trialDays())->toDateString(),
            'created_at' => $start,
            'updated_at' => $start,
        ]);

        return $user;
    }

    public function test_register_form_offers_esencial_and_pro_and_preselects_from_the_landing(): void
    {
        $this->get('/register')->assertOk()
            ->assertSee('¿Qué paquete quieres probar gratis?')
            ->assertSee('value="Esencial"', false)
            ->assertSee('value="Pro" checked', false)
            ->assertDontSee('value="Consultorio"', false);

        $this->get('/register?plan=Esencial')->assertSee('value="Esencial" checked', false);
    }

    public function test_trial_of_esencial_gives_only_esencial_features(): void
    {
        $user = $this->register('Esencial');

        $this->assertSame('Esencial', $user->trial_plan);
        $this->assertSame('Esencial', $user->latestSubscription->plan->name);
        $this->assertTrue($user->latestSubscription->isTrial());

        $this->get('/pacientes')->assertOk();
        $this->get('/planes-alimenticios')->assertForbidden()->assertSee('Probar Pro gratis');
        $this->get('/reportes')->assertForbidden();
    }

    public function test_trial_of_pro_gives_pro_features(): void
    {
        $this->register('Pro');

        $this->get('/planes-alimenticios')->assertOk();
        $this->get('/reportes')->assertOk();
    }

    public function test_without_a_choice_the_default_trial_plan_is_used(): void
    {
        $user = $this->register(null);

        $this->assertSame(config('nutrilife.default_trial_plan'), $user->latestSubscription->plan->name);
    }

    public function test_consultorio_cannot_be_chosen_as_trial(): void
    {
        $this->post('/register', [
            'name' => 'X', 'email' => 'x@nutrilife.test', 'professional_license' => '7654322', 'degree_number' => 'LN-1',
            'password' => 'password-segura-1', 'password_confirmation' => 'password-segura-1', 'trial_plan' => 'Consultorio',
        ])->assertSessionHasErrors('trial_plan');

        $this->assertDatabaseMissing('users', ['email' => 'x@nutrilife.test']);
    }

    public function test_switching_the_trial_plan_keeps_the_same_end_date(): void
    {
        $user = $this->trialUser('Esencial');
        $endsAt = $user->latestSubscription->trialEndsAt();

        $this->actingAs($user)->get('/contratar')->assertOk()
            ->assertSee('Estás probando el paquete Esencial')
            ->assertSee('Probar Pro gratis');

        $this->actingAs($user)->post(route('subscription.trial.switch', SubscriptionPlan::where('name', 'Pro')->first()))
            ->assertRedirect(route('dashboard.index'));

        $subscription = $user->fresh()->latestSubscription;
        $this->assertSame('Pro', $subscription->plan->name);
        $this->assertTrue($subscription->isTrial());
        $this->assertTrue($endsAt->equalTo($subscription->trialEndsAt()));

        $this->actingAs($user->fresh())->get('/planes-alimenticios')->assertOk();

        // Y puede regresar a Esencial
        $this->actingAs($user->fresh())->post(route('subscription.trial.switch', SubscriptionPlan::where('name', 'Esencial')->first()));
        $this->assertSame('Esencial', $user->fresh()->latestSubscription->plan->name);
    }

    public function test_cannot_switch_to_consultorio_or_after_the_trial_or_with_a_paid_plan(): void
    {
        $consultorio = SubscriptionPlan::where('name', 'Consultorio')->first();
        $pro = SubscriptionPlan::where('name', 'Pro')->first();

        $user = $this->trialUser('Esencial');
        $this->actingAs($user)->post(route('subscription.trial.switch', $consultorio))->assertSessionHasErrors('trial');
        $this->assertSame('Esencial', $user->fresh()->latestSubscription->plan->name);

        $expired = $this->trialUser('Esencial', Subscription::trialDays() + 2);
        // La prueba vencida ni siquiera llega: el panel la manda a contratar
        $this->actingAs($expired)->post(route('subscription.trial.switch', $pro))->assertRedirect(route('subscription.plans'));
        $this->assertSame('Esencial', $expired->fresh()->latestSubscription->plan->name);

        $paid = User::factory()->create();
        Subscription::create([
            'user_id' => $paid->id, 'subscription_plan_id' => SubscriptionPlan::where('name', 'Esencial')->value('id'),
            'status' => Subscription::STATUS_ACTIVE, 'starts_at' => now()->subDays(3)->toDateString(),
            'current_period_ends_at' => now()->addDays(20)->toDateString(),
        ]);
        $this->actingAs($paid)->post(route('subscription.trial.switch', $pro))->assertSessionHasErrors('trial');
        $this->assertSame('Esencial', $paid->fresh()->latestSubscription->plan->name);
    }

    public function test_trial_badge_says_which_plan_is_being_tried(): void
    {
        $user = $this->trialUser('Pro');

        $this->assertSame('Prueba Pro', Subscription::accountTypeFor($user)['label']);
        $this->actingAs($user)->get('/perfil')->assertOk()->assertSee('Prueba gratuita del paquete Pro');
    }
}
