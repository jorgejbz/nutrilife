<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Consultorios: subperfiles de nutriólogo, separación de pacientes,
 * funciones Pro con la licencia de la clínica y aislamiento entre clínicas.
 */
class ClinicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Esencial' => 249, 'Pro' => 399, 'Consultorio' => 599] as $name => $price) {
            SubscriptionPlan::create(['name' => $name, 'price' => $price, 'billing_period' => 'mensual', 'active' => true]);
        }
    }

    private function account(string $plan = 'Consultorio', string $status = Subscription::STATUS_ACTIVE, int $daysLeft = 20): User
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

    private function member(User $clinic, array $attributes = []): User
    {
        $member = User::factory()->create($attributes + ['email_verified_at' => null]);
        $member->forceFill(['clinic_id' => $clinic->id])->save();

        return $member->fresh();
    }

    private function patientFor(User $user, string $first = 'Ana'): Patient
    {
        return Patient::create([
            'user_id' => $user->id,
            'first_name' => $first,
            'last_name' => 'Prueba',
            'email' => strtolower($first).'.'.$user->id.'@pacientes.test',
            'active' => true,
        ]);
    }

    private function memberPayload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Lic. Andrea Solís',
            'email' => 'Andrea.Solis@Clinica.test',
            'password' => 'password-segura-1',
            'password_confirmation' => 'password-segura-1',
            'professional_license' => '12345678',
            'degree_number' => 'ln-2019-0457',
        ];
    }

    public function test_clinic_creates_several_members_with_their_own_credentials(): void
    {
        $clinic = $this->account();

        $this->actingAs($clinic)->post('/clinica/equipo', $this->memberPayload())->assertRedirect();
        $this->actingAs($clinic)->post('/clinica/equipo', $this->memberPayload([
            'name' => 'Lic. Diego Navarro', 'email' => 'diego@clinica.test', 'professional_license' => '87654321',
        ]))->assertRedirect();

        $this->assertSame(2, $clinic->clinicMembers()->count());

        $andrea = User::where('email', 'andrea.solis@clinica.test')->firstOrFail();
        $this->assertSame($clinic->id, $andrea->clinic_id);
        $this->assertSame(Role::NUTRITIONIST, (int) $andrea->role_id);
        $this->assertSame(User::ACCOUNT_ACTIVE, $andrea->account_status);
        $this->assertNull($andrea->email_verified_at, 'El correo NO se marca como verificado');
        $this->assertSame('LN-2019-0457', $andrea->degree_number);
        $this->assertSame(0, $andrea->subscriptions()->count(), 'Sin suscripción ni cobro individual');
        $this->assertTrue(AuditLog::where('action', 'clinic.member_created')->where('subject_id', $andrea->id)->exists());

        // Inicia sesión con SUS credenciales
        auth()->logout();
        $this->post('/login', ['email' => 'andrea.solis@clinica.test', 'password' => 'password-segura-1'])
            ->assertRedirect(route('dashboard.index', absolute: false));
        $this->assertAuthenticatedAs($andrea);
    }

    public function test_member_cannot_be_turned_into_admin_or_moved_by_the_request(): void
    {
        $clinic = $this->account();
        $other = $this->account();

        $this->actingAs($clinic)->post('/clinica/equipo', $this->memberPayload([
            'role_id' => Role::ADMIN, 'clinic_id' => $other->id, 'account_status' => 'pendiente',
        ]))->assertRedirect();

        $member = User::where('email', 'andrea.solis@clinica.test')->firstOrFail();
        $this->assertSame(Role::NUTRITIONIST, (int) $member->role_id);
        $this->assertSame($clinic->id, $member->clinic_id);
        $this->assertSame(User::ACCOUNT_ACTIVE, $member->account_status);
    }

    public function test_members_get_pro_features_from_the_clinic_license(): void
    {
        $clinic = $this->account();
        $member = $this->member($clinic);

        $this->assertTrue($member->hasFeature('diet_plans'));

        foreach (['/dashboard', '/pacientes', '/citas', '/consultas', '/pagos', '/progreso', '/planes-alimenticios', '/alimentos', '/platillos', '/perfil'] as $url) {
            $this->actingAs($member)->get($url)->assertOk();
        }

        // No contrata por su cuenta ni entra a la sección de la clínica
        $this->actingAs($member)->get('/contratar')->assertForbidden();
        $this->actingAs($member)->get('/clinica')->assertForbidden();
        $this->actingAs($member)->get('/clinica/equipo')->assertForbidden();
    }

    public function test_members_lose_access_when_the_clinic_license_expires(): void
    {
        $clinic = $this->account('Consultorio', Subscription::STATUS_ACTIVE, -2);
        $member = $this->member($clinic);

        $this->assertFalse($member->hasFeature('diet_plans'));
        $this->actingAs($member)->get('/pacientes')->assertStatus(402)->assertSee('licencia de tu clínica');
        $this->actingAs($member)->get('/perfil')->assertOk();
    }

    public function test_each_nutritionist_only_manages_own_patients_but_the_clinic_sees_all(): void
    {
        $clinic = $this->account();
        $a = $this->member($clinic, ['name' => 'Nutrióloga A']);
        $b = $this->member($clinic, ['name' => 'Nutriólogo B']);

        $patientA = $this->patientFor($a, 'Alicia');
        $patientB = $this->patientFor($b, 'Bruno');
        $own = $this->patientFor($clinic, 'Carla');

        $this->actingAs($a)->get('/pacientes')->assertSee('Alicia')->assertDontSee('Bruno');
        $this->actingAs($a)->get("/pacientes/{$patientB->id}")->assertForbidden();
        $this->actingAs($b)->get("/pacientes/{$patientA->id}/edit")->assertForbidden();

        $this->actingAs($clinic)->get('/clinica/pacientes')
            ->assertOk()
            ->assertSee('Alicia')->assertSee('Bruno')->assertSee('Carla')
            ->assertSee('Nutrióloga A')->assertSee('Nutriólogo B');

        // Filtro por nutriólogo
        $this->actingAs($clinic)->get('/clinica/pacientes?nutriologo='.$b->id)
            ->assertSee('Bruno')->assertDontSee('Alicia');

        $this->assertSame(3, Patient::whereIn('user_id', $clinic->clinicNutritionistIds())->count());
        $this->assertNotNull($own);
    }

    public function test_clinic_dashboard_and_agenda_show_real_data(): void
    {
        $clinic = $this->account();
        $member = $this->member($clinic, ['name' => 'Nutrióloga Agenda']);
        $patient = $this->patientFor($member, 'Daniela');

        Appointment::create(['user_id' => $member->id, 'patient_id' => $patient->id, 'appointment_at' => now()->addHour(), 'status' => 'Confirmada', 'reason' => 'Control']);
        Appointment::create(['user_id' => $member->id, 'patient_id' => $patient->id, 'appointment_at' => now()->addDays(3), 'status' => 'Pendiente', 'reason' => 'Seguimiento']);

        $this->actingAs($clinic)->get('/clinica')
            ->assertOk()
            ->assertViewHas('totals', fn ($totals) => $totals['members'] === 1 && $totals['patients'] === 1 && $totals['upcoming_appointments'] === 2)
            ->assertSee('Nutrióloga Agenda')
            ->assertSee('Daniela');

        $this->actingAs($clinic)->get('/clinica/agenda?estado=Pendiente')
            ->assertOk()->assertSee('Seguimiento')->assertDontSee('Control');
    }

    public function test_a_clinic_cannot_see_or_change_members_of_another_clinic(): void
    {
        $clinic = $this->account();
        $other = $this->account();
        $foreign = $this->member($other, ['name' => 'Ajeno Nutriólogo']);
        $this->patientFor($foreign, 'Ximena');

        $this->actingAs($clinic)->get("/clinica/equipo/{$foreign->id}")->assertNotFound();
        $this->actingAs($clinic)->get("/clinica/equipo/{$foreign->id}/editar")->assertNotFound();
        $this->actingAs($clinic)->put("/clinica/equipo/{$foreign->id}", $this->memberPayload())->assertNotFound();
        $this->actingAs($clinic)->patch("/clinica/equipo/{$foreign->id}/estado", ['action' => 'desactivar'])->assertNotFound();
        $this->actingAs($clinic)->get('/clinica/pacientes')->assertDontSee('Ximena');
        $this->actingAs($clinic)->get('/clinica/pacientes?nutriologo='.$foreign->id)->assertDontSee('Ximena');

        $this->assertSame(User::ACCOUNT_ACTIVE, $foreign->fresh()->account_status);
    }

    public function test_individual_accounts_cannot_open_clinic_sections(): void
    {
        $pro = $this->account('Pro');

        $this->actingAs($pro)->get('/clinica')->assertForbidden();
        $this->actingAs($pro)->post('/clinica/equipo', $this->memberPayload())->assertForbidden();
        $this->assertSame(0, User::whereNotNull('clinic_id')->count());
    }

    public function test_clinic_deactivates_and_reactivates_a_member(): void
    {
        $clinic = $this->account();
        $member = $this->member($clinic);

        $this->actingAs($clinic)->patch("/clinica/equipo/{$member->id}/estado", ['action' => 'desactivar'])->assertRedirect();
        $this->assertSame(User::ACCOUNT_DISABLED, $member->fresh()->account_status);

        // La sesión abierta del subperfil se cierra y no puede volver a entrar
        $this->actingAs($member->fresh())->get('/pacientes')->assertRedirect('/login');
        $this->post('/login', ['email' => $member->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($clinic)->patch("/clinica/equipo/{$member->id}/estado", ['action' => 'activar'])->assertRedirect();
        $this->assertSame(User::ACCOUNT_ACTIVE, $member->fresh()->account_status);
    }

    public function test_clinic_edits_member_data_and_optional_password(): void
    {
        $clinic = $this->account();
        $member = $this->member($clinic);
        $originalHash = $member->password;

        $this->actingAs($clinic)->put("/clinica/equipo/{$member->id}", [
            'name' => 'Nombre Nuevo', 'email' => $member->email, 'professional_license' => '7654321', 'degree_number' => '',
            'password' => '', 'password_confirmation' => '',
        ])->assertRedirect();

        $member->refresh();
        $this->assertSame('Nombre Nuevo', $member->name);
        $this->assertSame('7654321', $member->professional_license);
        $this->assertSame($originalHash, $member->password, 'Sin contraseña nueva se conserva la actual');
    }

    public function test_member_limit_is_centralized_in_config(): void
    {
        config(['nutrilife.clinic.max_members' => 1]);
        $clinic = $this->account();
        $this->member($clinic);

        $this->actingAs($clinic)->get('/clinica/equipo/nuevo')->assertForbidden();
        $this->actingAs($clinic)->post('/clinica/equipo', $this->memberPayload())->assertForbidden();
    }

    public function test_profile_shows_clinic_label_only_for_the_clinic_account(): void
    {
        $clinic = $this->account();
        $member = $this->member($clinic);
        $pro = $this->account('Pro');

        $this->actingAs($clinic)->get('/perfil')->assertOk()->assertSee('Perfil de la clínica')->assertSee('Clínica');
        $this->actingAs($member)->get('/perfil')->assertOk()->assertSee('Nutriólogo')->assertSee($clinic->name)->assertDontSee('Perfil de la clínica');
        $this->actingAs($pro)->get('/perfil')->assertOk()->assertSee('Nutriólogo')->assertDontSee('Perfil de la clínica');

        $this->assertSame('Clínica', $clinic->profileTypeLabel());
        $this->assertSame('Nutriólogo', $member->profileTypeLabel());
        $this->assertSame('Nutriólogo', $pro->profileTypeLabel());
    }
}
