<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Evaluation;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API de la app móvil (routes/api.php). Cuida el contrato que lee la app
 * Android: login con access_code + correo, token Bearer y formatos de fecha
 * sin zona horaria (LocalDateTime / LocalDate en Kotlin).
 */
class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    private User $nutri;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->nutri = User::factory()->create();
        $this->patient = Patient::create([
            'user_id' => $this->nutri->id, 'first_name' => 'Ana', 'last_name' => 'Prueba',
            'email' => 'ana@x.test', 'active' => true,
        ]);
    }

    private function login(array $overrides = [])
    {
        return $this->postJson('/api/login', array_merge([
            'access_code' => $this->nutri->access_code,
            'email' => 'ana@x.test',
        ], $overrides));
    }

    private function token(): string
    {
        return $this->login()->assertOk()->json('token');
    }

    public function test_login_returns_token_and_patient(): void
    {
        $this->login()
            ->assertOk()
            ->assertJsonPath('patient.id', $this->patient->id)
            ->assertJsonPath('patient.nutriologo', $this->nutri->name)
            ->assertJsonStructure(['token']);
    }

    public function test_login_rejects_wrong_code_or_email_with_same_message(): void
    {
        $wrongCode = $this->nutri->access_code === '000000' ? '111111' : '000000';

        $a = $this->login(['access_code' => $wrongCode])->assertStatus(401)->json('message');
        $b = $this->login(['email' => 'otro@x.test'])->assertStatus(401)->json('message');

        $this->assertSame($a, $b);
    }

    public function test_inactive_patient_or_disabled_nutritionist_cannot_log_in(): void
    {
        $this->patient->update(['active' => false]);
        $this->login()->assertStatus(401);

        $this->patient->update(['active' => true]);
        $this->nutri->forceFill(['account_status' => User::ACCOUNT_DISABLED])->save();
        $this->login()->assertStatus(403);
    }

    public function test_protected_routes_require_token_and_logout_revokes_it(): void
    {
        $this->getJson('/api/me')->assertStatus(401);

        $token = $this->token();
        $this->withToken($token)->getJson('/api/me')->assertOk()->assertJsonPath('email', 'ana@x.test');
        $this->withToken($token)->postJson('/api/logout')->assertOk();

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/me')->assertStatus(401);
    }

    public function test_dates_have_no_timezone_suffix_for_the_android_parser(): void
    {
        Consultation::forceCreate([
            'user_id' => $this->nutri->id, 'patient_id' => $this->patient->id,
            'consultation_at' => '2026-09-30 10:05:00', 'reason' => 'Control', 'next_visit' => '2026-10-15',
        ]);
        Evaluation::forceCreate([
            'patient_id' => $this->patient->id,
            'evaluation_date' => '2026-09-02', 'weight' => 82.4, 'height' => 162,
        ]);

        $token = $this->token();

        $this->withToken($token)->getJson('/api/consultations')->assertOk()
            ->assertJsonPath('0.consultation_at', '2026-09-30 10:05:00')
            ->assertJsonPath('0.next_visit', '2026-10-15');

        $this->withToken($token)->getJson('/api/evaluations')->assertOk()
            ->assertJsonPath('0.evaluation_date', '2026-09-02');
    }

    public function test_patient_only_sees_and_changes_own_appointments(): void
    {
        $other = Patient::create([
            'user_id' => $this->nutri->id, 'first_name' => 'Beto', 'last_name' => 'Otro',
            'email' => 'beto@x.test', 'active' => true,
        ]);
        $foreign = Appointment::forceCreate([
            'user_id' => $this->nutri->id, 'patient_id' => $other->id,
            'appointment_at' => now()->addDays(5)->setTime(10, 0), 'reason' => 'X', 'status' => 'Pendiente',
        ]);
        $own = Appointment::forceCreate([
            'user_id' => $this->nutri->id, 'patient_id' => $this->patient->id,
            'appointment_at' => now()->addDays(5)->setTime(12, 0), 'reason' => 'Y', 'status' => 'Pendiente',
        ]);

        $token = $this->token();

        $this->withToken($token)->getJson('/api/appointments')->assertOk()
            ->assertJsonCount(1)->assertJsonPath('0.id', $own->id)->assertJsonPath('0.puede_modificar', true);

        $this->withToken($token)->postJson("/api/appointments/{$foreign->id}/cancel")->assertStatus(404);

        $this->withToken($token)->postJson("/api/appointments/{$own->id}/cancel", ['reason' => 'viaje'])
            ->assertOk()->assertJsonPath('cita.status', 'Cancelada');

        $this->withToken($token)->postJson("/api/appointments/{$own->id}/cancel")->assertStatus(422);
    }
}
