<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\DietPlan;
use App\Models\Evaluation;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\AdvancedReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Exportación a PDF (plan alimenticio, progreso y reporte) y reportes
 * avanzados: solo Pro, Consultorio y prueba gratuita; Esencial no.
 */
class PdfExportAndReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Esencial' => 249, 'Pro' => 399, 'Consultorio' => 599] as $name => $price) {
            SubscriptionPlan::create(['name' => $name, 'price' => $price, 'billing_period' => 'mensual', 'active' => true]);
        }
    }

    private function nutritionist(string $plan = 'Pro', string $status = Subscription::STATUS_ACTIVE): User
    {
        $user = User::factory()->create();

        Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => SubscriptionPlan::where('name', $plan)->value('id'),
            'status' => $status,
            'starts_at' => now()->subDays(10)->toDateString(),
            'current_period_ends_at' => now()->addDays(15)->toDateString(),
        ]);

        return $user;
    }

    /** Paciente con evaluaciones, consulta, cita, pagos y un plan con una comida. */
    private function patientWithHistory(User $user): array
    {
        $patient = Patient::create([
            'user_id' => $user->id, 'first_name' => 'Mariana', 'last_name' => 'López Castillo',
            'sex' => 'Femenino', 'goal' => 'Bajar 5 kg', 'active' => true,
            'email' => 'mariana'.$user->id.'@example.test', 'phone' => '3312345678', 'birth_date' => '1994-03-10',
        ]);

        Evaluation::create(['patient_id' => $patient->id, 'evaluation_date' => now()->subDays(40)->toDateString(), 'weight' => 80, 'height' => 162, 'bmi' => 30.5, 'body_fat' => 36, 'waist' => 98]);
        Evaluation::create(['patient_id' => $patient->id, 'evaluation_date' => now()->subDays(5)->toDateString(), 'weight' => 76.5, 'height' => 162, 'bmi' => 29.1, 'body_fat' => 34, 'waist' => 94]);

        Consultation::create(['user_id' => $user->id, 'patient_id' => $patient->id, 'consultation_at' => now()->subDays(5), 'reason' => 'Seguimiento', 'recommendations' => 'Caminar 30 minutos']);
        Appointment::create(['user_id' => $user->id, 'patient_id' => $patient->id, 'appointment_at' => now()->subDays(5), 'reason' => 'Seguimiento', 'status' => 'Completada']);
        Appointment::create(['user_id' => $user->id, 'patient_id' => $patient->id, 'appointment_at' => now()->subDays(12), 'reason' => 'Revisión', 'status' => 'Cancelada']);

        Payment::create(['user_id' => $user->id, 'patient_id' => $patient->id, 'amount' => 600, 'paid_at' => now()->subDays(5), 'concept' => 'Consulta de seguimiento', 'payment_method' => 'Transferencia', 'status' => Payment::STATUS_PAID]);
        Payment::create(['user_id' => $user->id, 'patient_id' => $patient->id, 'amount' => 400, 'paid_at' => now()->subDays(3), 'concept' => 'Plan alimenticio', 'payment_method' => 'Efectivo', 'status' => Payment::STATUS_PAID]);

        $plan = DietPlan::create([
            'patient_id' => $patient->id, 'user_id' => $user->id, 'name' => 'Déficit moderado 1,850 kcal',
            'goal' => 'Bajar de peso', 'instructions' => 'Tomar 2 L de agua al día.',
            'target_kcal' => 1850, 'target_protein_g' => 116, 'target_carbs_g' => 231, 'target_lipids_g' => 51,
            'starts_at' => now()->subDays(5)->toDateString(), 'active' => true,
        ]);
        $meal = $plan->meals()->create(['meal_name' => 'Desayuno', 'meal_order' => 1, 'meal_time' => '08:00']);
        $meal->items()->create([
            'custom_name' => 'Avena con fruta', 'custom_portion_description' => '1 taza',
            'custom_kcal' => 320, 'custom_protein_g' => 10, 'custom_carbs_g' => 55, 'custom_lipids_g' => 6, 'grams' => 250,
        ]);

        return [$patient, $plan];
    }

    /* ---------------- Exportación a PDF ---------------- */

    public function test_pro_downloads_the_diet_plan_as_pdf(): void
    {
        $user = $this->nutritionist();
        [, $plan] = $this->patientWithHistory($user);

        $response = $this->actingAs($user)->get(route('diet-plans.pdf', $plan));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('plan-alimenticio-mariana-lopez-castillo.pdf', $response->headers->get('content-disposition'));
    }

    public function test_pro_downloads_the_patient_progress_as_pdf(): void
    {
        $user = $this->nutritionist();
        [$patient] = $this->patientWithHistory($user);

        $response = $this->actingAs($user)->get(route('progreso.pdf', $patient));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_pdf_of_another_nutritionists_patient_or_plan_is_not_available(): void
    {
        $owner = $this->nutritionist();
        [$patient, $plan] = $this->patientWithHistory($owner);
        $other = $this->nutritionist();

        $this->actingAs($other)->get(route('progreso.pdf', $patient))->assertForbidden();
        $this->actingAs($other)->get(route('diet-plans.pdf', $plan))->assertNotFound();
    }

    public function test_esencial_cannot_export_pdf_and_does_not_see_the_button(): void
    {
        $user = $this->nutritionist('Esencial');
        [$patient] = $this->patientWithHistory($user);

        $this->actingAs($user)->get(route('progreso.pdf', $patient))->assertForbidden();
        $this->actingAs($user)->get(route('progreso.show', $patient))->assertOk()->assertDontSee(route('progreso.pdf', $patient));
    }

    public function test_pro_sees_the_export_buttons(): void
    {
        $user = $this->nutritionist();
        [$patient, $plan] = $this->patientWithHistory($user);

        $this->actingAs($user)->get(route('progreso.show', $patient))->assertSee(route('progreso.pdf', $patient));
        $this->actingAs($user)->get(route('diet-plans.show', $plan))->assertSee(route('diet-plans.pdf', $plan));
    }

    /* ---------------- Reportes avanzados ---------------- */

    public static function allowedAccounts(): array
    {
        return [
            'Pro' => ['Pro', Subscription::STATUS_ACTIVE],
            'Consultorio' => ['Consultorio', Subscription::STATUS_ACTIVE],
            'Prueba gratuita de Pro' => ['Pro', Subscription::STATUS_TRIAL],
        ];
    }

    #[DataProvider('allowedAccounts')]
    public function test_reports_page_is_available_for_pro_consultorio_and_trial(string $plan, string $status): void
    {
        $user = $this->nutritionist($plan, $status);
        $this->patientWithHistory($user);

        $this->actingAs($user)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Reportes de tu consulta')
            ->assertSee('$1,000.00', false)
            ->assertSee('Transferencia')
            ->assertSee('Mariana López Castillo');
    }

    public function test_esencial_cannot_open_reports_and_has_no_menu_item(): void
    {
        $user = $this->nutritionist('Esencial');

        $this->actingAs($user)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($user)->get(route('reports.csv'))->assertForbidden();
        $this->actingAs($user)->get(route('dashboard.index'))->assertOk()->assertDontSee(route('reports.index'));
    }

    public function test_report_csv_and_pdf_downloads(): void
    {
        $user = $this->nutritionist();
        $this->patientWithHistory($user);

        $csv = $this->actingAs($user)->get(route('reports.csv', ['periodo' => '3m']));
        $csv->assertOk();
        $content = $csv->streamedContent();
        $this->assertStringContainsString('Ingresos cobrados (MXN)', $content);
        $this->assertStringContainsString('Mariana López Castillo', $content);

        $pdf = $this->actingAs($user)->get(route('reports.pdf', ['periodo' => '3m']));
        $pdf->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_report_numbers_only_count_the_nutritionists_own_data(): void
    {
        $user = $this->nutritionist();
        $this->patientWithHistory($user);
        $this->patientWithHistory($this->nutritionist()); // otro nutriólogo con los mismos datos

        $report = AdvancedReport::fromRequest($user, Request::create('/', 'GET', ['periodo' => '3m']))->all();

        $this->assertSame(1000.0, $report['kpis']['income']['value']);
        $this->assertSame(1, $report['kpis']['consultations']['value']);
        $this->assertSame(1, $report['kpis']['completed']['value']);
        $this->assertSame(50.0, $report['kpis']['cancellation_rate']['value']);
        $this->assertSame(500.0, $report['kpis']['average_ticket']['value']);
        $this->assertSame(1, $report['clinical']['patients']);
        $this->assertSame(-3.5, $report['clinical']['avg_weight_change']);
        $this->assertSame(1, $report['clinical']['lost']);
        $this->assertSame('Transferencia', $report['income_by_method'][0]['label']);
        $this->assertSame(1, $report['diet_plans']['created']);
    }

    public function test_custom_date_range_and_invalid_ranges_fall_back_to_default(): void
    {
        $user = $this->nutritionist();

        $custom = AdvancedReport::fromRequest($user, Request::create('/', 'GET', ['desde' => '2026-01-01', 'hasta' => '2026-03-31']));
        $this->assertNull($custom->preset());
        $this->assertSame('2026-01-01', $custom->from()->toDateString());
        $this->assertCount(3, $custom->all()['monthly']);

        $inverted = AdvancedReport::fromRequest($user, Request::create('/', 'GET', ['desde' => '2026-05-01', 'hasta' => '2026-01-01']));
        $this->assertSame(AdvancedReport::DEFAULT_PRESET, $inverted->preset());

        $garbage = AdvancedReport::fromRequest($user, Request::create('/', 'GET', ['desde' => '2026-02-31', 'hasta' => 'mañana']));
        $this->assertSame(AdvancedReport::DEFAULT_PRESET, $garbage->preset());
    }
}
