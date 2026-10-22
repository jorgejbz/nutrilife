<?php

namespace Tests\Feature;

use App\Models\DietPlanDish;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\AccountActivation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

/**
 * Ruta /perfil, información profesional en el perfil, logo del login,
 * tiempo de activación configurable, platillo de ejemplo retirado y
 * proxy de confianza (túnel HTTPS).
 */
class PlatformUpdatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SubscriptionPlan::create(['name' => 'Esencial', 'price' => 249, 'billing_period' => 'mensual', 'active' => true]);
        SubscriptionPlan::create(['name' => 'Pro', 'price' => 399, 'billing_period' => 'mensual', 'active' => true]);
    }

    private function pro(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        Subscription::create(['user_id' => $user->id, 'subscription_plan_id' => SubscriptionPlan::where('name', 'Pro')->value('id'), 'status' => 'activa', 'starts_at' => now()->toDateString(), 'current_period_ends_at' => now()->addMonth()->toDateString()]);

        return $user;
    }

    public function test_perfil_is_the_profile_route_and_profile_redirects(): void
    {
        $user = $this->pro();

        $this->actingAs($user)->get('/perfil')->assertOk()->assertSee('Mi perfil');
        $this->actingAs($user)->get('/profile')->assertRedirect('/perfil')->assertStatus(301);
        $this->assertSame(url('/perfil'), route('perfil'));

        auth()->logout();
        $this->get('/perfil')->assertRedirect('/login');
    }

    public function test_internal_links_point_to_perfil(): void
    {
        $this->actingAs($this->pro())->get('/dashboard')
            ->assertOk()
            ->assertSee(route('perfil'), false)
            ->assertDontSee(url('/profile'), false);
    }

    public function test_profile_shows_professional_data_and_states(): void
    {
        $user = $this->pro(['professional_license' => '12345678', 'degree_number' => 'LN-2020-1', 'email_verified_at' => now()]);

        $this->actingAs($user)->get('/perfil')
            ->assertOk()
            ->assertSee('Cédula profesional')->assertSee('12345678')->assertSee('Registrada')
            ->assertSee('Título profesional')->assertSee('LN-2020-1')->assertSee('Registrado')
            ->assertSee('Activa')
            ->assertSee('Verificado');
    }

    public function test_missing_professional_data_shows_no_registrado_and_email_is_not_faked(): void
    {
        // Independiente del .env local (que puede activar la simulación)
        config(['nutrilife.simulated_email_verification' => false]);
        $user = $this->pro(['professional_license' => null, 'degree_number' => null, 'email_verified_at' => null]);

        $this->actingAs($user)->get('/perfil')->assertSee('No registrado')->assertSee('Sin verificar');
        $this->assertSame('unverified', $user->emailVerificationState()['state']);

        // Verificación simulada: solo visual y nunca en producción
        config(['nutrilife.simulated_email_verification' => true]);
        $this->assertSame('simulated', $user->emailVerificationState()['state']);
        $this->actingAs($user)->get('/perfil')->assertSee('Simulado (entorno de pruebas)');
        $this->assertNull($user->fresh()->email_verified_at);

        $this->app['env'] = 'production';
        $this->assertSame('unverified', $user->emailVerificationState()['state']);
    }

    public function test_login_logo_links_to_the_landing_page(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('href="'.route('landing').'" class="brand-logo-link"', false)
            ->assertSee('aria-label="NutriLife: ir a la página principal"', false)
            ->assertSee('alt="NutriLife"', false);

        $this->get(route('landing'))->assertOk();
    }

    public function test_activation_delay_comes_from_config(): void
    {
        foreach ([1, 5, 10] as $minutes) {
            config(['nutrilife.account_activation.delay_minutes' => $minutes]);

            $this->post('/register', [
                'name' => "Lic. Prueba {$minutes}", 'email' => "delay{$minutes}@x.test",
                'professional_license' => (string) (1000000 + $minutes), 'degree_number' => 'LN-1',
                'password' => 'password-segura-1', 'password_confirmation' => 'password-segura-1',
            ])->assertRedirect('/registro/gracias');

            $user = User::where('email', "delay{$minutes}@x.test")->firstOrFail();
            $this->assertSame(User::ACCOUNT_PENDING, $user->account_status);
            $this->assertEqualsWithDelta($minutes * 60, now()->diffInSeconds($user->activates_at), 5);

            // Antes de tiempo no entra; al cumplirse, sí.
            $this->assertFalse(AccountActivation::refresh($user));
            $this->travel($minutes)->minutes();
            $this->assertTrue(AccountActivation::refresh($user->fresh()));
            $this->travelBack();
        }
    }

    public function test_user_cannot_skip_activation_with_request_parameters(): void
    {
        config(['nutrilife.account_activation.delay_minutes' => 10]);

        $this->post('/register', [
            'name' => 'Lic. Atajo', 'email' => 'atajo@x.test', 'professional_license' => '7777777', 'degree_number' => 'LN-1',
            'password' => 'password-segura-1', 'password_confirmation' => 'password-segura-1',
            'account_status' => 'activa', 'activates_at' => now()->subYear()->toDateTimeString(), 'email_verified_at' => now()->toDateTimeString(),
        ]);

        $user = User::where('email', 'atajo@x.test')->firstOrFail();
        $this->assertSame(User::ACCOUNT_PENDING, $user->account_status);
        $this->assertTrue($user->activates_at->isFuture());
        $this->assertNull($user->email_verified_at);
    }

    public function test_demo_dish_migration_only_removes_that_dish(): void
    {
        $pro = User::factory()->create(['email' => 'pro@nutrilife.test']);
        $other = User::factory()->create();

        $demo = DietPlanDish::create(['user_id' => $pro->id, 'name' => 'Ensalada de Nopales con Queso Panela']);
        $keepOwn = DietPlanDish::create(['user_id' => $pro->id, 'name' => 'Otro platillo propio']);
        $keepOther = DietPlanDish::create(['user_id' => $other->id, 'name' => 'Ensalada de Nopales con Queso Panela']);
        $system = DietPlanDish::create(['user_id' => null, 'name' => 'Platillo del sistema']);

        $migration = require database_path('migrations/2026_10_22_000002_remove_demo_dish_ensalada_de_nopales.php');
        $migration->up();

        $this->assertNull(DietPlanDish::find($demo->id));
        $this->assertNotNull(DietPlanDish::find($keepOwn->id));
        $this->assertNotNull(DietPlanDish::find($keepOther->id));
        $this->assertNotNull(DietPlanDish::find($system->id));
        $this->assertSame(3, DB::table('diet_plan_dishes')->count());
    }

    public function test_https_tunnel_through_trusted_local_proxy_generates_https_urls(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'demo.trycloudflare.com', 'X-Forwarded-Port' => '443'])
            ->get('/login')
            ->assertOk()
            ->assertSee('action="https://demo.trycloudflare.com/login"', false);

        // Un cliente externo no puede falsificar las cabeceras
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'malicioso.test'])
            ->get('/login')
            ->assertDontSee('malicioso.test', false);
    }

    public function test_tunnel_visits_use_built_assets_instead_of_the_vite_dev_server(): void
    {
        // Simula `npm run dev` corriendo (public/hot → http://[::1]:5173)
        $hot = storage_path('framework/testing-vite.hot');
        file_put_contents($hot, 'http://[::1]:5173');
        Vite::useHotFile($hot);

        try {
            // En esta PC: recarga en vivo de Vite
            $this->get('http://localhost/login')->assertOk()->assertSee('[::1]:5173', false);

            // Por el túnel: JS compilado (botón de tema, confirmaciones…)
            $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
                ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'demo.trycloudflare.com', 'X-Forwarded-Port' => '443'])
                ->get('/login')
                ->assertOk()
                ->assertDontSee('5173', false)
                ->assertSee('/build/assets/theme-', false);
        } finally {
            @unlink($hot);
        }
    }
}
