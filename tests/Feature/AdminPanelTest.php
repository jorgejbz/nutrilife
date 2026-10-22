<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Administrador global: indicadores, listado, detalle de clínicas,
 * acciones con bitácora y barreras de seguridad.
 */
class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Esencial' => 249, 'Pro' => 399, 'Consultorio' => 599] as $name => $price) {
            SubscriptionPlan::create(['name' => $name, 'price' => $price, 'billing_period' => 'mensual', 'active' => true]);
        }
    }

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::ADMIN, 'name' => 'Admin Global']);
    }

    private function account(string $plan, string $status = Subscription::STATUS_ACTIVE, int $daysLeft = 20, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);

        Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => SubscriptionPlan::where('name', $plan)->value('id'),
            'status' => $status,
            'starts_at' => now()->subDays(10)->toDateString(),
            'current_period_ends_at' => now()->addDays($daysLeft)->toDateString(),
        ]);

        return $user;
    }

    private function patientFor(User $user, int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            Patient::create(['user_id' => $user->id, 'first_name' => 'P'.$i, 'last_name' => 'X', 'email' => "p{$i}.{$user->id}@x.test", 'active' => true]);
        }
    }

    public function test_dashboard_counts_accounts_by_plan_without_double_counting_members(): void
    {
        $admin = $this->admin();
        $this->account('Esencial', Subscription::STATUS_TRIAL, 3);
        $this->account('Esencial');
        $pro = $this->account('Pro');
        $clinic = $this->account('Consultorio', attributes: ['name' => 'Clínica Norte']);
        $member = User::factory()->create();
        $member->forceFill(['clinic_id' => $clinic->id])->save();
        $pending = User::factory()->create();
        $pending->forceFill(['account_status' => User::ACCOUNT_PENDING])->save();
        $this->patientFor($pro, 2);
        $this->patientFor($member, 3);

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertViewHas('totals', function (array $totals) {
                return $totals['main_accounts'] === 5        // prueba, esencial, pro, clínica, pendiente
                    && $totals['trial'] === 1
                    && $totals['esencial'] === 1
                    && $totals['pro'] === 1
                    && $totals['consultorio'] === 1
                    && $totals['members'] === 1               // aparte de las principales
                    && $totals['patients'] === 5
                    && $totals['pending_activation'] === 1;
            });
    }

    public function test_accounts_list_filters_and_clinic_detail_shows_members(): void
    {
        $admin = $this->admin();
        $clinic = $this->account('Consultorio', attributes: ['name' => 'Clínica Norte']);
        $member = User::factory()->create(['name' => 'Subperfil Uno']);
        $member->forceFill(['clinic_id' => $clinic->id])->save();
        $this->patientFor($member, 4);
        $this->account('Pro', attributes: ['name' => 'Doctor Pro']);

        $this->actingAs($admin)->get('/admin/cuentas')->assertOk()->assertSee('Clínica Norte')->assertSee('Doctor Pro')->assertSee('Subperfil Uno');
        $this->actingAs($admin)->get('/admin/cuentas?plan=Pro')->assertSee('Doctor Pro')->assertDontSee('Clínica Norte');
        $this->actingAs($admin)->get('/admin/cuentas?tipo=subperfil')->assertSee('Subperfil Uno')->assertDontSee('Doctor Pro');
        $this->actingAs($admin)->get('/admin/cuentas?tipo=clinica')->assertSee('Clínica Norte')->assertDontSee('Doctor Pro');
        $this->actingAs($admin)->get('/admin/cuentas?q=norte')->assertSee('Clínica Norte')->assertDontSee('Doctor Pro');

        $this->actingAs($admin)->get("/admin/cuentas/{$clinic->id}")
            ->assertOk()
            ->assertSee('Subperfiles de nutriólogo')
            ->assertSee('Subperfil Uno')
            ->assertViewHas('orgPatients', 4);
    }

    public function test_non_admins_cannot_open_or_post_to_the_admin_panel(): void
    {
        $pro = $this->account('Pro');
        $target = $this->account('Esencial');

        foreach (['/admin', '/admin/cuentas', "/admin/cuentas/{$target->id}", '/admin/cobros', '/admin/auditoria'] as $url) {
            $this->actingAs($pro)->get($url)->assertForbidden();
        }

        $this->actingAs($pro)->patch("/admin/cuentas/{$target->id}/estado", ['action' => 'desactivar'])->assertForbidden();
        $this->actingAs($pro)->post("/admin/cuentas/{$target->id}/plan", ['subscription_plan_id' => 3, 'months' => 12, 'payment' => 'ninguno'])->assertForbidden();
        $this->assertSame(User::ACCOUNT_ACTIVE, $target->fresh()->account_status);

        auth()->logout();
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_public_registration_never_creates_admins(): void
    {
        SubscriptionPlan::query()->update(['active' => true]);

        $this->post('/register', [
            'name' => 'Intruso', 'email' => 'intruso@x.test', 'role_id' => Role::ADMIN, 'clinic_id' => 1,
            'password' => 'password-segura-1', 'password_confirmation' => 'password-segura-1',
            'professional_license' => '1234567', 'degree_number' => 'LN-1',
        ])->assertRedirect();

        $user = User::where('email', 'intruso@x.test')->firstOrFail();
        $this->assertSame(Role::NUTRITIONIST, (int) $user->role_id);
        $this->assertNull($user->clinic_id);
    }

    public function test_profile_update_cannot_escalate_privileges(): void
    {
        $pro = $this->account('Pro');

        $this->actingAs($pro)->patch('/perfil', ['name' => 'Nuevo', 'email' => $pro->email, 'role_id' => Role::ADMIN, 'account_status' => 'activa'])->assertRedirect('/perfil');

        $this->assertSame(Role::NUTRITIONIST, (int) $pro->fresh()->role_id);
    }

    public function test_admin_deactivates_reactivates_and_activates_with_audit(): void
    {
        $admin = $this->admin();
        $target = $this->account('Pro');
        $pending = User::factory()->create();
        $pending->forceFill(['account_status' => User::ACCOUNT_PENDING, 'activates_at' => now()->addHour(), 'activated_at' => null])->save();

        $this->actingAs($admin)->patch("/admin/cuentas/{$target->id}/estado", ['action' => 'desactivar', 'reason' => 'Prueba'])->assertRedirect();
        $this->assertSame(User::ACCOUNT_DISABLED, $target->fresh()->account_status);
        $this->assertTrue(AuditLog::where('action', 'account.deactivated')->where('subject_id', $target->id)->where('user_id', $admin->id)->exists());

        $this->actingAs($admin)->patch("/admin/cuentas/{$target->id}/estado", ['action' => 'reactivar'])->assertRedirect();
        $this->assertSame(User::ACCOUNT_ACTIVE, $target->fresh()->account_status);

        $this->actingAs($admin)->patch("/admin/cuentas/{$pending->id}/estado", ['action' => 'activar'])->assertRedirect();
        $this->assertSame(User::ACCOUNT_ACTIVE, $pending->fresh()->account_status);
        $this->assertSame(1, $pending->subscriptions()->count(), 'Empieza su prueba al activarse');

        // No se modifica a sí mismo ni a otros administradores
        $this->actingAs($admin)->patch("/admin/cuentas/{$admin->id}/estado", ['action' => 'desactivar'])->assertForbidden();
    }

    public function test_disabling_a_clinic_blocks_its_members(): void
    {
        $admin = $this->admin();
        $clinic = $this->account('Consultorio');
        $member = User::factory()->create();
        $member->forceFill(['clinic_id' => $clinic->id])->save();

        $this->actingAs($admin)->patch("/admin/cuentas/{$clinic->id}/estado", ['action' => 'desactivar'])->assertRedirect();

        $this->actingAs($member->fresh())->get('/dashboard')->assertRedirect('/login');
    }

    public function test_admin_assigns_plan_and_confirms_pending_manual_payment(): void
    {
        $admin = $this->admin();
        $target = $this->account('Esencial');
        $pro = SubscriptionPlan::where('name', 'Pro')->first();

        $this->actingAs($admin)->post("/admin/cuentas/{$target->id}/plan", ['subscription_plan_id' => $pro->id, 'months' => 3, 'payment' => 'pendiente'])->assertRedirect();

        $target->unsetRelation('latestSubscription');
        $this->assertSame('Pro', $target->latestSubscription->plan->name);
        $this->assertTrue($target->hasFeature('diet_plans'));

        $payment = SubscriptionPayment::where('subscription_id', $target->latestSubscription->id)->firstOrFail();
        $this->assertSame('pendiente', $payment->status);
        $this->assertSame('Manual', $payment->payment_method);

        // El dueño de la cuenta no puede confirmar su propio cobro
        $this->actingAs($target)->patch("/admin/cobros/{$payment->id}/completar")->assertForbidden();
        $this->assertSame('pendiente', $payment->fresh()->status);

        $this->actingAs($admin)->patch("/admin/cobros/{$payment->id}/completar")->assertRedirect();
        $payment->refresh();
        $this->assertSame('pagado', $payment->status);
        $this->assertSame($admin->id, (int) $payment->status_changed_by);
        $this->assertNotNull($payment->status_changed_at);
        $this->assertTrue(AuditLog::where('action', 'subscription_payment.completed')->exists());
    }

    public function test_non_manual_payments_are_not_confirmed_by_hand(): void
    {
        $admin = $this->admin();
        $target = $this->account('Pro');
        $payment = new SubscriptionPayment;
        $payment->forceFill(['subscription_id' => $target->latestSubscription->id, 'amount' => 399, 'paid_at' => now(), 'payment_method' => 'Pasarela', 'status' => 'pendiente', 'reference' => 'GW-1'])->save();

        $this->actingAs($admin)->patch("/admin/cobros/{$payment->id}/completar")->assertSessionHas('error');
        $this->assertSame('pendiente', $payment->fresh()->status);
    }

    public function test_create_admin_command_is_the_controlled_way_to_create_admins(): void
    {
        $this->artisan('nutrilife:create-admin', ['email' => 'Admin@NutriLife.test', '--password' => 'Segura-Admin-2026'])->assertSuccessful();

        $admin = User::where('email', 'admin@nutrilife.test')->firstOrFail();
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue(Hash::check('Segura-Admin-2026', $admin->password));

        // Un correo de nutriólogo no se convierte en administrador
        $pro = $this->account('Pro');
        $this->artisan('nutrilife:create-admin', ['email' => $pro->email, '--password' => 'Segura-Admin-2026'])->assertFailed();
        $this->assertFalse($pro->fresh()->isAdmin());

        // Contraseña débil: se rechaza
        $this->artisan('nutrilife:create-admin', ['email' => 'otro@nutrilife.test', '--password' => '123'])->assertFailed();

        $this->post('/login', ['email' => 'admin@nutrilife.test', 'password' => 'Segura-Admin-2026'])->assertRedirect('/admin');
    }
}
