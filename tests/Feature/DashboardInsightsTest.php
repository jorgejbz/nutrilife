<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Evaluation;
use App\Models\Pathology;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\DashboardStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Sección «Análisis» del dashboard: los 10 datos propuestos en mejoras.jpeg,
 * siempre del nutriólogo autenticado y sin pacientes eliminados.
 */
class DashboardInsightsTest extends TestCase
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

    private function patient(User $user, ?string $sex = null, ?string $birth = null): Patient
    {
        return Patient::create([
            'user_id' => $user->id, 'first_name' => 'P', 'last_name' => uniqid(), 'email' => uniqid().'@t.mx',
            'active' => true, 'sex' => $sex, 'birth_date' => $birth,
        ]);
    }

    private function payment(Patient $patient, string $at, float $amount, string $status = 'Pagado', string $method = 'Efectivo'): void
    {
        Payment::create(['user_id' => $patient->user_id, 'patient_id' => $patient->id, 'amount' => $amount, 'paid_at' => $at, 'payment_method' => $method, 'status' => $status]);
    }

    private function appointment(Patient $patient, string $at, string $status): void
    {
        Appointment::create(['user_id' => $patient->user_id, 'patient_id' => $patient->id, 'appointment_at' => $at, 'status' => $status]);
    }

    private function evaluation(Patient $patient, string $date, ?float $weight, ?float $bmi): void
    {
        Evaluation::create(['patient_id' => $patient->id, 'evaluation_date' => $date, 'weight' => $weight, 'bmi' => $bmi]);
    }

    public function test_finance_agenda_and_consultation_series(): void
    {
        $p = $this->patient($this->user);

        // 1 y 2: cobrado vs. pendiente y métodos de pago
        $this->payment($p, '2026-09-10 10:00', 1000, 'Pagado', 'Transferencia');
        $this->payment($p, '2026-10-05 10:00', 450, 'Pagado', 'Efectivo');
        $this->payment($p, '2026-10-06 10:00', 300, 'Pendiente', 'Tarjeta');
        $this->payment($p, '2026-10-07 10:00', 999, 'Cancelado', 'Efectivo'); // no cuenta

        // 4 y 10: estados y demanda (martes 20 y 13 a las 9, jueves 1 a las 17)
        $this->appointment($p, '2026-10-20 09:00', 'Completada');
        $this->appointment($p, '2026-10-13 09:30', 'Confirmada');
        $this->appointment($p, '2026-10-01 17:00', 'Pendiente');
        $this->appointment($p, '2026-09-15 10:00', 'Cancelada');

        // 5: consultas
        foreach (['2026-08-03', '2026-10-02', '2026-10-10'] as $date) {
            Consultation::create(['user_id' => $this->user->id, 'patient_id' => $p->id, 'consultation_at' => $date.' 10:00']);
        }

        // Datos de otro nutriólogo: no deben aparecer
        $other = $this->patient($this->account());
        $this->payment($other, '2026-10-05 10:00', 5000);
        $this->appointment($other, '2026-10-20 09:00', 'Cancelada');

        $stats = (new DashboardStats($this->user, 6))->all();
        $charts = $stats['charts'];

        $this->assertEquals([0, 0, 0, 0, 1000, 450], $charts['income']['data']);
        $this->assertEquals([0, 0, 0, 0, 0, 300], $charts['income']['pending']);

        $this->assertSame(['Efectivo', 'Transferencia', 'Tarjeta', 'Otro'], $charts['paymentMethods']['labels']);
        $this->assertEquals([450, 1000, 0, 0], $charts['paymentMethods']['data']);

        $this->assertSame([0, 0, 0, 0, 0, 1], $charts['appointmentStatus']['series']['Completada']);
        $this->assertSame([0, 0, 0, 0, 1, 0], $charts['appointmentStatus']['series']['Cancelada']);
        $this->assertSame(25, $stats['cancellationRate']); // 1 de 4

        $this->assertSame([0, 0, 0, 1, 0, 2], $charts['consultations']['data']); // may–oct: ago = 1, oct = 2

        $demand = $stats['demand'];
        $this->assertSame(3, $demand['total']); // sin la cancelada
        $this->assertSame(range(9, 17), $demand['hours']);
        $this->assertSame(2, $demand['grid'][1][0]); // martes 9:00
        $this->assertSame(1, $demand['grid'][3][8]); // jueves 17:00
        $this->assertSame(2, $demand['max']);
    }

    public function test_patient_profile_series(): void
    {
        // 6: sexo y edad (hoy 20/10/2026)
        $ana = $this->patient($this->user, 'Femenino', '1996-05-01');   // 30 → 30-44
        $luis = $this->patient($this->user, 'Masculino', '2010-01-01'); // 16 → 0-17
        $eva = $this->patient($this->user, 'Femenino', '1966-10-21');   // 59 → 45-59
        $this->patient($this->user);                                     // sin datos

        $deleted = $this->patient($this->user, 'Otro', '1990-01-01');

        // 7: patologías (la del paciente eliminado no cuenta)
        $diabetes = Pathology::create(['name' => 'Diabetes tipo 2']);
        $hta = Pathology::create(['name' => 'Hipertensión']);
        foreach ([[$ana, $diabetes], [$eva, $diabetes], [$eva, $hta], [$deleted, $hta], [$deleted, $diabetes]] as [$patient, $pathology]) {
            DB::table('patient_pathologies')->insert(['patient_id' => $patient->id, 'pathology_id' => $pathology->id]);
        }

        // 8 y 9: IMC de la última evaluación y evolución de peso
        $this->evaluation($ana, '2026-01-10', 80, 31);
        $this->evaluation($ana, '2026-09-10', 74, 27.5);   // bajó 6 kg → sobrepeso
        $this->evaluation($luis, '2026-03-01', 60, 17);
        $this->evaluation($luis, '2026-08-01', 62, 18.4);  // subió 2 kg → bajo peso
        $this->evaluation($eva, '2026-07-01', 70, 24.9);
        $this->evaluation($eva, '2026-10-01', 70.3, 41);   // mantuvo → obesidad III
        $this->evaluation($deleted, '2026-10-01', 90, 35);

        $deleted->delete();

        $stats = (new DashboardStats($this->user, 6))->all();
        $charts = $stats['charts'];

        $this->assertSame(['0-17', '18-29', '30-44', '45-59', '60+'], $charts['demographics']['labels']);
        $this->assertSame([0, 0, 1, 1, 0], $charts['demographics']['series']['Femenino']);
        $this->assertSame([1, 0, 0, 0, 0], $charts['demographics']['series']['Masculino']);
        $this->assertSame([0, 0, 0, 0, 0], $charts['demographics']['series']['Otro']);
        $this->assertSame(1, $charts['demographics']['missing']);

        $this->assertSame(['Diabetes tipo 2', 'Hipertensión'], $charts['pathologies']['labels']);
        $this->assertSame([2, 1], $charts['pathologies']['data']);

        $this->assertSame(['Bajo peso', 'Normal', 'Sobrepeso', 'Obesidad I', 'Obesidad II', 'Obesidad III'], $charts['bmi']['labels']);
        $this->assertSame([1, 0, 1, 0, 0, 1], $charts['bmi']['data']);

        $weight = $stats['weightEvolution'];
        $this->assertSame(3, $weight['patients']);
        $this->assertSame([1, 1, 1], [$weight['lost'], $weight['kept'], $weight['gained']]);
        $this->assertSame(33, $weight['lost_pct']);
        $this->assertSame(-1.2, $weight['average_change']); // (-6 + 2 + 0.3) / 3
    }

    public function test_dashboard_renders_the_new_sections_and_empty_states(): void
    {
        $this->actingAs($this->user)->get('/dashboard')->assertOk()
            ->assertSee('Estado de las citas por mes')
            ->assertSee('Ingresos por método de pago')
            ->assertSee('Demanda por día y hora')
            ->assertSee('No hay consultas registradas en este periodo.')
            ->assertSee('Ningún paciente tiene patologías registradas.')
            ->assertSee('Aún no hay evaluaciones con IMC.')
            ->assertSee('Se necesita al menos un paciente con dos evaluaciones de peso.');

        $p = $this->patient($this->user, 'Femenino', '1990-01-01');
        $this->appointment($p, '2026-10-20 09:00', 'Completada');
        $this->payment($p, '2026-10-05 10:00', 450);

        $this->actingAs($this->user)->get('/dashboard?meses=12')->assertOk()
            ->assertSee('id="appointmentStatusChart"', false)
            ->assertSee('id="paymentMethodsChart"', false)
            ->assertSee('id="demographicsChart"', false)
            ->assertSee('class="dashboard-heatmap"', false)
            ->assertSee('Mar 09:00 — 1 cita')
            ->assertSee('Detalle de los últimos 12 meses');
    }
}
