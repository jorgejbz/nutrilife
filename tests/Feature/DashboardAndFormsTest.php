<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\DashboardStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Dashboard con datos reales, estados de paciente, validación de fechas y
 * cierre de sesión.
 */
class DashboardAndFormsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-20 12:00:00'));
        SubscriptionPlan::create(['name' => 'Pro', 'price' => 399, 'billing_period' => 'mensual', 'active' => true]);
        $this->user = $this->account();
    }

    private function account(): User
    {
        $user = User::factory()->create(['created_at' => now()->subYear()]);
        Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => SubscriptionPlan::value('id'),
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now()->subDays(5)->toDateString(),
            'current_period_ends_at' => now()->addDays(25)->toDateString(),
        ]);

        return $user;
    }

    private function patient(User $user, bool $active = true, ?string $createdAt = null): Patient
    {
        $patient = new Patient;
        $patient->forceFill([
            'user_id' => $user->id, 'first_name' => 'P', 'last_name' => uniqid(), 'email' => uniqid().'@t.mx',
            'active' => $active, 'created_at' => $createdAt ?? now(), 'updated_at' => now(),
        ])->save();

        return $patient;
    }

    private function appointment(Patient $patient, string $at, string $status): void
    {
        Appointment::create(['user_id' => $patient->user_id, 'patient_id' => $patient->id, 'appointment_at' => $at, 'status' => $status]);
    }

    private function payment(Patient $patient, string $at, float $amount, string $status = 'Pagado'): void
    {
        Payment::create(['user_id' => $patient->user_id, 'patient_id' => $patient->id, 'amount' => $amount, 'paid_at' => $at, 'concept' => 'Consulta', 'payment_method' => 'Efectivo', 'status' => $status]);
    }

    public function test_dashboard_numbers_come_from_the_database_and_only_from_the_user(): void
    {
        $a = $this->patient($this->user, true, '2026-08-10');
        $b = $this->patient($this->user, true, '2026-10-02');
        $this->patient($this->user, false, '2026-09-15');

        // Lunes 19 a domingo 25 de octubre de 2026
        $this->appointment($a, '2026-10-20 09:00', 'Completada');
        $this->appointment($a, '2026-10-20 17:00', 'Confirmada');
        $this->appointment($b, '2026-10-22 10:00', 'Pendiente');
        $this->appointment($b, '2026-10-21 10:00', 'Cancelada');   // no cuenta
        $this->appointment($b, '2026-10-05 10:00', 'Completada');

        $this->payment($a, '2026-10-20 09:55', 500);
        $this->payment($b, '2026-10-05 10:55', 450);
        $this->payment($b, '2026-09-10 10:00', 1000);
        $this->payment($b, '2026-10-06 10:00', 300, 'Pendiente');

        // Datos de OTRO nutriólogo: no deben aparecer
        $other = $this->account();
        $x = $this->patient($other);
        $this->appointment($x, '2026-10-20 11:00', 'Confirmada');
        $this->payment($x, '2026-10-20 11:00', 9999);

        $stats = (new DashboardStats($this->user, 6))->all();

        $this->assertSame(3, $stats['totalPatients']);
        $this->assertSame(2, $stats['activePatients']);
        $this->assertSame(1, $stats['inactivePatients']);
        $this->assertSame(1, $stats['newPatientsThisMonth']);
        $this->assertSame(2, $stats['todayAppointmentsCount']);
        $this->assertSame(1, $stats['todayCompletedCount']);
        $this->assertSame(4, $stats['monthAppointments']);
        $this->assertEquals(950.0, $stats['monthIncome']);
        $this->assertSame(-5, $stats['incomeGrowth']);           // 950 vs 1000
        $this->assertSame(1, $stats['pendingPaymentsCount']);
        $this->assertSame(100, $stats['monthAttendance']);       // 2 pasadas, 2 completadas

        $charts = $stats['charts'];
        $this->assertSame(['May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct'], $charts['patients']['labels']);
        $this->assertSame([0, 0, 0, 1, 2, 3], $charts['patients']['total']);
        $this->assertSame([0, 2, 0, 1, 0, 0, 0], $charts['appointments']['data']); // martes 20: 2 citas; miércoles 21: solo una cancelada
        $this->assertSame(1, $charts['appointments']['today']);
        $this->assertEquals([0, 0, 0, 0, 1000, 950], $charts['income']['data']);

        // La vista recibe esos datos
        $this->actingAs($this->user)->get('/dashboard')
            ->assertOk()
            ->assertSee('"total":[0,0,0,1,2,3]', false)
            ->assertSee('$950', false)
            ->assertDontSee('9,999', false);

        // 12 meses
        $this->assertCount(12, (new DashboardStats($this->user, 12))->all()['charts']['income']['data']);
    }

    public function test_dashboard_without_data_shows_empty_states(): void
    {
        $this->actingAs($this->user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Aún no hay pacientes registrados.')
            ->assertSee('No hay citas esta semana.')
            ->assertSee('No tienes citas pendientes ni confirmadas.');
    }

    public function test_patient_status_is_shown_with_text_and_distinct_style(): void
    {
        $this->patient($this->user, true);
        $this->patient($this->user, false);

        $this->actingAs($this->user)->get('/pacientes')
            ->assertOk()
            ->assertSee('nl-patient-status is-active', false)
            ->assertSee('nl-patient-status is-inactive', false)
            ->assertSee('fa-pause', false)
            ->assertSee('data-status-filter="inactivos"', false);
    }

    public function test_dates_are_validated_on_the_server(): void
    {
        $patient = $this->patient($this->user);

        $this->actingAs($this->user)->post('/pacientes', [
            'first_name' => 'Ana', 'last_name' => 'Ruiz', 'email' => 'ana@t.mx', 'birth_date' => now()->addDay()->toDateString(),
        ])->assertSessionHasErrors(['birth_date' => 'La fecha de nacimiento no puede ser futura.']);

        $this->actingAs($this->user)->post('/pacientes', [
            'first_name' => 'Ana', 'last_name' => 'Ruiz', 'email' => 'ana@t.mx', 'birth_date' => '1850-01-01',
        ])->assertSessionHasErrors('birth_date');

        $this->actingAs($this->user)->post("/pacientes/{$patient->id}/evaluaciones", [
            'evaluation_date' => now()->addDay()->toDateString(), 'weight' => 70, 'height' => 170,
        ])->assertSessionHasErrors(['evaluation_date' => 'La fecha de la evaluación no puede ser futura.']);

        $this->actingAs($this->user)->post('/citas', [
            'patient_id' => $patient->id, 'appointment_at' => now()->subDays(3)->format('Y-m-d\TH:i'), 'status' => 'Pendiente',
        ])->assertSessionHasErrors('appointment_at');

        // Una cita ya completada sí puede registrarse en el pasado
        $this->actingAs($this->user)->post('/citas', [
            'patient_id' => $patient->id, 'appointment_at' => now()->subDays(3)->format('Y-m-d\TH:i'), 'status' => 'Completada',
        ])->assertSessionHasNoErrors();

        // Fecha válida: se guarda y se recupera igual (AAAA-MM-DD)
        $this->actingAs($this->user)->post('/pacientes', [
            'first_name' => 'Ana', 'last_name' => 'Ruiz', 'email' => 'ana@t.mx', 'birth_date' => '1990-04-12',
        ])->assertSessionHasNoErrors();
        $saved = Patient::where('email', 'ana@t.mx')->firstOrFail();
        $this->assertSame('1990-04-12', $saved->birth_date->toDateString());
        $this->actingAs($this->user)->get("/pacientes/{$saved->id}/edit")->assertSee('value="1990-04-12"', false);
    }

    public function test_logout_uses_post_csrf_and_private_pages_are_not_cached(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $response->assertSee('data-logout', false);

        $this->actingAs($this->user)->get('/logout')->assertStatus(405);

        $this->actingAs($this->user)->post('/logout')
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Cerraste sesión correctamente.');
        $this->assertGuest();

        $this->get('/dashboard')->assertRedirect(route('login'));
    }
}
