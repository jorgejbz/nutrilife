<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\DietPlan;
use App\Models\Evaluation;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DemoActivityCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-13 12:00'));
    }

    private function account(string $email): User
    {
        return User::factory()->create(['email' => $email, 'created_at' => now()->subMonths(4)]);
    }

    public function test_adds_new_patients_and_fills_the_agenda_through_next_month_without_overlaps(): void
    {
        $pro = $this->account('pro@nutrilife.test');
        $esencial = $this->account('esencial@nutrilife.test');

        // Paciente de demostración con una cita que ya pasó y quedó Pendiente
        $demo = Patient::create(['user_id' => $pro->id, 'first_name' => 'Ana', 'last_name' => 'Demo', 'email' => 'ana@pacientes-pro.test', 'active' => true]);
        Evaluation::create(['patient_id' => $demo->id, 'evaluation_date' => '2026-10-20', 'weight' => 80, 'height' => 165, 'bmi' => 29.4, 'body_fat' => 35, 'waist' => 92, 'hip' => 101]);
        $past = Appointment::create(['user_id' => $pro->id, 'patient_id' => $demo->id, 'appointment_at' => '2026-11-03 10:00', 'reason' => 'Seguimiento', 'status' => 'Pendiente']);

        // Paciente real (correo que no es .test): no se toca
        $real = Patient::create(['user_id' => $pro->id, 'first_name' => 'Real', 'last_name' => 'Paciente', 'email' => 'real@gmail.com', 'active' => true]);
        Appointment::create(['user_id' => $pro->id, 'patient_id' => $real->id, 'appointment_at' => '2026-11-05 12:00', 'reason' => 'X', 'status' => 'Pendiente']);

        $this->artisan('nutrilife:demo-activity')->assertSuccessful();

        // La cita pasada quedó completada con consulta, evaluación y pago
        $this->assertSame('Completada', $past->fresh()->status);
        $this->assertTrue(Consultation::where('appointment_id', $past->id)->exists());
        $this->assertTrue(Payment::where('appointment_id', $past->id)->where('status', 'Pagado')->exists());
        $this->assertEqualsWithDelta(79.5, Evaluation::where('patient_id', $demo->id)->latest('evaluation_date')->value('weight'), 0.01);

        // El paciente real no cambió
        $this->assertSame(1, Appointment::where('patient_id', $real->id)->count());
        $this->assertSame('Pendiente', Appointment::where('patient_id', $real->id)->value('status'));

        // Pacientes nuevos este mes en ambas cuentas, y citas en diciembre
        foreach ([$pro, $esencial] as $user) {
            $this->assertGreaterThanOrEqual(3, Patient::where('user_id', $user->id)->where('created_at', '>=', '2026-11-01')->count());
            $this->assertGreaterThan(0, Appointment::where('user_id', $user->id)->whereBetween('appointment_at', ['2026-12-01', '2026-12-31 23:59:59'])->count());
            $this->assertSame(0, Appointment::where('user_id', $user->id)->where('appointment_at', '>', '2026-12-31 23:59:59')->count());
            $this->assertGreaterThan(0, Payment::where('user_id', $user->id)->where('paid_at', '>=', '2026-11-01')->count());
        }

        // Sin empalmes entre las citas nuevas que agendó el comando
        $open = Appointment::where('user_id', $pro->id)->where('status', '!=', 'Cancelada')
            ->where('appointment_at', '>', now())->orderBy('appointment_at')->pluck('appointment_at');
        foreach ($open as $i => $at) {
            if ($i > 0) {
                $this->assertGreaterThanOrEqual(60, Carbon::parse($open[$i - 1])->diffInMinutes(Carbon::parse($at)));
            }
        }

        // Pro recibe planes alimenticios; Esencial no
        $this->assertSame(0, DietPlan::where('user_id', $esencial->id)->count());
    }

    public function test_running_twice_does_not_duplicate(): void
    {
        $this->account('pro@nutrilife.test');
        $this->account('esencial@nutrilife.test');

        $this->artisan('nutrilife:demo-activity')->assertSuccessful();
        $counts = [Patient::count(), Appointment::count(), Payment::count(), Consultation::count()];

        $this->artisan('nutrilife:demo-activity')->assertSuccessful();
        $this->assertSame($counts, [Patient::count(), Appointment::count(), Payment::count(), Consultation::count()]);
    }

    public function test_missing_accounts_are_skipped(): void
    {
        $this->artisan('nutrilife:demo-activity')
            ->expectsOutputToContain('No existe la cuenta pro@nutrilife.test')
            ->assertSuccessful();
    }
}
