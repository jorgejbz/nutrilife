<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Registro con cédula y título + activación simulada de la cuenta.
 */
class AccountActivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['nutrilife.account_activation.delay_minutes' => 5]);
        SubscriptionPlan::create(['name' => 'Esencial', 'price' => 249, 'billing_period' => 'mensual', 'active' => true]);
    }

    private function register(array $overrides = [])
    {
        return $this->post('/register', $overrides + [
            'name' => 'Lic. Ana Torres',
            'email' => 'Ana.Torres@Correo.test',
            'professional_license' => ' 1234 5678 ',
            'degree_number' => 'ln-2020-0001',
            'password' => 'password-segura-1',
            'password_confirmation' => 'password-segura-1',
        ]);
    }

    public function test_registration_stores_professional_data_and_leaves_account_pending(): void
    {
        $this->register()
            ->assertRedirect(route('register.thanks'))
            ->assertSessionHas('registered_name', 'Lic. Ana Torres');

        $this->assertGuest();

        $user = User::where('email', 'ana.torres@correo.test')->firstOrFail();
        $this->assertSame('12345678', $user->professional_license);
        $this->assertSame('LN-2020-0001', $user->degree_number);
        $this->assertSame(User::ACCOUNT_PENDING, $user->account_status);
        $this->assertNull($user->activated_at);
        $this->assertNull($user->professional_verified_at, 'El registro no verifica la cédula.');
        $this->assertEqualsWithDelta(now()->addMinutes(5)->timestamp, $user->activates_at->timestamp, 5);
        $this->assertSame(0, $user->subscriptions()->count(), 'La prueba empieza al activarse, no al registrarse.');
    }

    public function test_thanks_page_message(): void
    {
        $this->register();

        $this->get(route('register.thanks'))
            ->assertOk()
            ->assertSee('¡Gracias por registrarte en NutriLife', false)
            ->assertSee('Hemos recibido tu información')
            ->assertSee('aproximadamente 5 minutos');
    }

    public function test_professional_fields_are_validated(): void
    {
        $this->register(['professional_license' => '12AB', 'degree_number' => ''])
            ->assertSessionHasErrors([
                'professional_license' => 'La cédula profesional debe tener 7 u 8 dígitos, sin letras ni guiones.',
                'degree_number' => 'Escribe el número de tu título profesional.',
            ]);

        User::factory()->create(['professional_license' => '12345678']);
        $this->register(['email' => 'otra@correo.test'])
            ->assertSessionHasErrors(['professional_license' => 'Ya existe una cuenta registrada con esta cédula profesional.']);
    }

    public function test_a_manipulated_request_cannot_activate_or_verify_the_account(): void
    {
        $this->register([
            'account_status' => 'activa',
            'activated_at' => now()->toDateTimeString(),
            'activates_at' => now()->subYear()->toDateTimeString(),
            'professional_verified_at' => now()->toDateTimeString(),
            'role_id' => 2,
        ]);

        $user = User::where('email', 'ana.torres@correo.test')->firstOrFail();
        $this->assertSame(User::ACCOUNT_PENDING, $user->account_status);
        $this->assertTrue($user->activates_at->isFuture());
        $this->assertNull($user->professional_verified_at);
        $this->assertTrue($user->isNutritionist());
    }

    public function test_pending_account_cannot_log_in_until_the_time_passes(): void
    {
        $this->register();

        $this->post('/login', ['email' => 'ana.torres@correo.test', 'password' => 'password-segura-1'])
            ->assertRedirect(route('account.pending'));
        $this->assertGuest();

        $this->get(route('account.pending'))->assertOk()->assertSee('Tu cuenta todavía no está habilitada');

        // Se cumple el tiempo: entra y empieza su prueba en ese momento
        $this->travel(6)->minutes();

        $this->post('/login', ['email' => 'ana.torres@correo.test', 'password' => 'password-segura-1'])
            ->assertRedirect(route('dashboard.index', absolute: false));
        $this->assertAuthenticated();

        $user = User::where('email', 'ana.torres@correo.test')->firstOrFail();
        $this->assertSame(User::ACCOUNT_ACTIVE, $user->account_status);
        $this->assertNotNull($user->activated_at);
        $this->assertNull($user->professional_verified_at, 'Activarse no es una verificación profesional.');

        $trial = $user->latestSubscription;
        $this->assertSame(Subscription::STATUS_TRIAL, $trial->status);
        $this->assertEqualsWithDelta(now()->timestamp, $trial->created_at->timestamp, 5);
    }

    public function test_pending_session_is_closed_on_private_routes(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['account_status' => User::ACCOUNT_PENDING, 'activates_at' => now()->addHour()])->save();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('account.pending'));
        $this->assertGuest();
    }

    public function test_existing_accounts_are_not_blocked(): void
    {
        // Las cuentas creadas antes de este flujo tienen account_status = 'activa' (default)
        $user = User::factory()->create();

        $this->assertSame(User::ACCOUNT_ACTIVE, $user->fresh()->account_status);
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->assertAuthenticated();
        $this->get('/dashboard')->assertOk();
    }

    public function test_activation_delay_is_configurable(): void
    {
        config(['nutrilife.account_activation.delay_minutes' => 0]);

        $this->register();

        $this->post('/login', ['email' => 'ana.torres@correo.test', 'password' => 'password-segura-1']);
        $this->assertAuthenticated();
    }
}
