<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PaymentReferenceService;
use App\Services\PlanCheckout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Referencia interna de NutriLife (internal_reference) para todos los pagos.
 */
class PaymentReferenceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-22 10:00:00');

        foreach (['Esencial' => 249, 'Pro' => 399, 'Consultorio' => 599] as $name => $price) {
            SubscriptionPlan::create(['name' => $name, 'price' => $price, 'billing_period' => 'mensual', 'active' => true]);
        }

        $this->user = $this->account('Pro');
        $this->patient = $this->patientFor($this->user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function account(string $plan): User
    {
        $user = User::factory()->create();
        Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => SubscriptionPlan::where('name', $plan)->value('id'),
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now()->subDays(5)->toDateString(),
            'current_period_ends_at' => now()->addDays(25)->toDateString(),
        ]);

        return $user;
    }

    private function patientFor(User $user, string $name = 'Lucía'): Patient
    {
        return Patient::create(['user_id' => $user->id, 'first_name' => $name, 'last_name' => 'Ramos', 'email' => strtolower($name).uniqid().'@x.test', 'active' => true]);
    }

    private function store(string $method, array $extra = [])
    {
        return $this->actingAs($this->user)->post('/pagos', $extra + [
            'patient_id' => $this->patient->id,
            'amount' => 500,
            'paid_at' => now()->format('Y-m-d H:i'),
            'concept' => 'Consulta',
            'payment_method' => $method,
            'status' => 'Pagado',
        ]);
    }

    public function test_each_method_gets_its_own_prefix_and_unique_number(): void
    {
        foreach (['Efectivo' => 'EFE', 'Tarjeta' => 'TAR', 'Transferencia' => 'TRA', 'Otro' => 'OTR'] as $method => $prefix) {
            $this->store($method)->assertRedirect('/pagos')->assertSessionHas('success');
            $payment = Payment::latest('id')->first();
            $this->assertMatchesRegularExpression('/^'.$prefix.'-20261022-\d{6}$/', $payment->internal_reference, $method);
        }

        // Mismo método y mismo día: referencias distintas.
        $this->store('Efectivo');
        $this->store('Efectivo');
        $refs = Payment::pluck('internal_reference');
        $this->assertCount(6, $refs);
        $this->assertSame($refs->count(), $refs->unique()->count());
        $this->assertSame(2, Payment::where('internal_reference', 'like', 'EFE-%')->count() - 1);

        $this->assertTrue(AuditLog::where('action', 'payment.created')->where('details->reference', $refs->last())->exists());
    }

    public function test_reference_cannot_be_set_from_the_request(): void
    {
        $this->store('Tarjeta', ['internal_reference' => 'EFE-20990101-999999', 'reference' => 'TPV-778899']);

        $payment = Payment::latest('id')->first();
        $this->assertStringStartsWith('TAR-20261022-', $payment->internal_reference);
        $this->assertSame('TPV-778899', $payment->reference, 'La referencia externa se guarda aparte');

        $before = $payment->internal_reference;
        $this->actingAs($this->user)->put("/pagos/{$payment->id}", [
            'amount' => 500, 'paid_at' => now()->format('Y-m-d H:i'), 'concept' => 'Consulta',
            'payment_method' => 'Tarjeta', 'internal_reference' => 'HACK-1',
        ]);
        $this->assertSame($before, $payment->fresh()->internal_reference);

        // Ni siquiera desde código se puede sobrescribir directamente.
        $payment->internal_reference = 'MANUAL-1';
        $payment->save();
        $this->assertSame($before, $payment->fresh()->internal_reference);
    }

    public function test_reference_policy_when_editing_cancelling_or_changing_method(): void
    {
        $this->store('Efectivo', ['status' => 'Pendiente']);
        $pending = Payment::latest('id')->first();
        $first = $pending->internal_reference;
        $payload = ['amount' => 500, 'paid_at' => now()->format('Y-m-d H:i'), 'concept' => 'Consulta'];

        // Editar otros campos: la referencia no cambia.
        $this->actingAs($this->user)->put("/pagos/{$pending->id}", $payload + ['payment_method' => 'Efectivo', 'concept' => 'Otro concepto']);
        $this->assertSame($first, $pending->fresh()->internal_reference);

        // Pendiente que cambia de método: referencia nueva con el prefijo correcto.
        $this->actingAs($this->user)->put("/pagos/{$pending->id}", $payload + ['payment_method' => 'Transferencia'])
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'nueva referencia'));
        $second = $pending->fresh()->internal_reference;
        $this->assertStringStartsWith('TRA-', $second);
        $this->assertNotSame($first, $second);
        $this->assertTrue(AuditLog::where('action', 'payment.updated')->where('details->reference_from', $first)->where('details->reference_to', $second)->exists());

        // Completado: corregir el método conserva la referencia histórica.
        $this->actingAs($this->user)->patch("/pagos/{$pending->id}/completar")->assertRedirect();
        $this->actingAs($this->user)->put("/pagos/{$pending->id}", $payload + ['payment_method' => 'Tarjeta'])
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'se conserva'));
        $this->assertSame($second, $pending->fresh()->internal_reference);
        $this->assertSame('Tarjeta', $pending->fresh()->payment_method);

        // Cancelar conserva la referencia; las referencias no se reutilizan.
        $this->store('Efectivo', ['status' => 'Pendiente']);
        $toCancel = Payment::latest('id')->first();
        $ref = $toCancel->internal_reference;
        $this->actingAs($this->user)->patch("/pagos/{$toCancel->id}/cancelar");
        $this->assertSame($ref, $toCancel->fresh()->internal_reference);
        $toCancel->delete();
        $this->store('Efectivo');
        $this->assertNotSame($ref, Payment::latest('id')->first()->internal_reference);
        $this->assertNotContains($first, Payment::withTrashed()->pluck('internal_reference')->all(), 'La referencia sustituida no se reasigna');
    }

    public function test_references_are_listed_searchable_and_copyable(): void
    {
        $this->store('Transferencia', ['reference' => 'SPEI-4455']);
        $this->store('Efectivo');
        $tra = Payment::where('payment_method', 'Transferencia')->first();
        $efe = Payment::where('payment_method', 'Efectivo')->first();

        $this->actingAs($this->user)->get('/pagos')->assertOk()
            ->assertSee('Referencia y método')
            ->assertSee($tra->internal_reference)->assertSee('data-copy="'.$tra->internal_reference.'"', false)
            ->assertSee('Ref. externa:')->assertSee('SPEI-4455')
            ->assertSee('nl-ref--tra', false)->assertSee('nl-ref--efe', false);

        $this->actingAs($this->user)->get('/pagos?q='.$tra->internal_reference)->assertOk()
            ->assertViewHas('payments', fn ($page) => $page->total() === 1 && $page->first()->is($tra));
        $this->actingAs($this->user)->get('/pagos?metodo=Efectivo')->assertOk()
            ->assertViewHas('payments', fn ($page) => $page->total() === 1 && $page->first()->is($efe));

        $this->actingAs($this->user)->get("/pagos/{$tra->id}")->assertOk()
            ->assertSee('Referencia NutriLife')->assertSee($tra->internal_reference)
            ->assertSee('Referencia externa')->assertSee('SPEI-4455');

        $this->actingAs($this->user)->get('/pagos/create')->assertOk()->assertSee('EFE-20261022-');
        $this->actingAs($this->user)->get("/pagos/{$tra->id}/edit")->assertOk()->assertSee($tra->internal_reference);
    }

    public function test_simultaneous_generation_never_repeats_and_survives_a_lost_counter(): void
    {
        $refs = collect(range(1, 40))->map(fn () => PaymentReferenceService::generate('Efectivo'));
        $this->assertSame(40, $refs->unique()->count());

        // Pago con una referencia ya "ocupada" (dato importado): se salta.
        $this->store('Tarjeta');
        $taken = Payment::latest('id')->first()->internal_reference;
        DB::table('reference_counters')->delete();
        $next = PaymentReferenceService::generate('Tarjeta');
        $this->assertNotSame($taken, $next);
        $this->assertGreaterThan((int) substr($taken, -6), (int) substr($next, -6), 'El contador se reconstruye desde el número más alto');

        $this->assertSame(PaymentReferenceService::UNKNOWN_PREFIX, PaymentReferenceService::prefixFor(null));
        $this->assertSame('OTR', PaymentReferenceService::prefixFor('Manual'));
        $this->assertSame('TRA', PaymentReferenceService::prefixFor(' transferencia  BANCARIA '));
        $this->assertSame('TAR', PaymentReferenceService::prefixFor('Tarjeta de crédito'));
    }

    public function test_backfill_assigns_references_to_history_without_touching_other_data(): void
    {
        $sub = $this->user->latestSubscription;
        DB::table('payments')->insert([
            ['user_id' => $this->user->id, 'patient_id' => $this->patient->id, 'amount' => 100, 'paid_at' => '2026-09-01 10:00', 'payment_method' => 'Efectivo', 'status' => 'Pagado', 'reference' => null, 'created_at' => '2026-09-01 10:00', 'updated_at' => '2026-09-02 10:00', 'deleted_at' => null],
            ['user_id' => $this->user->id, 'patient_id' => $this->patient->id, 'amount' => 200, 'paid_at' => '2026-09-03 10:00', 'payment_method' => 'Transferencia', 'status' => 'Cancelado', 'reference' => 'TRF-0903', 'created_at' => '2026-09-03 10:00', 'updated_at' => '2026-09-03 10:00', 'deleted_at' => null],
            ['user_id' => $this->user->id, 'patient_id' => $this->patient->id, 'amount' => 300, 'paid_at' => '2026-09-04 10:00', 'payment_method' => '', 'status' => 'Pendiente', 'reference' => null, 'created_at' => '2026-09-04 10:00', 'updated_at' => '2026-09-04 10:00', 'deleted_at' => '2026-09-05 10:00'],
        ]);
        DB::table('subscription_payments')->insert([
            ['subscription_id' => $sub->id, 'amount' => 399, 'paid_at' => '2026-09-01', 'payment_method' => 'Simulado', 'status' => 'pagado', 'reference' => 'NL-PRO-202609', 'created_at' => '2026-09-01', 'updated_at' => '2026-09-01'],
            ['subscription_id' => $sub->id, 'amount' => 399, 'paid_at' => '2026-10-01', 'payment_method' => 'Manual', 'status' => 'pendiente', 'reference' => null, 'created_at' => '2026-10-01', 'updated_at' => '2026-10-01'],
        ]);

        $snapshot = fn () => [
            DB::table('payments')->orderBy('id')->get(['id', 'user_id', 'patient_id', 'amount', 'paid_at', 'payment_method', 'status', 'reference', 'updated_at', 'deleted_at'])->toJson(),
            DB::table('subscription_payments')->orderBy('id')->get(['id', 'amount', 'paid_at', 'payment_method', 'status', 'reference', 'updated_at'])->toJson(),
        ];
        $before = $snapshot();

        $this->artisan('nutrilife:payment-references --dry-run')->assertSuccessful();
        $this->assertSame(5, DB::table('payments')->whereNull('internal_reference')->count() + DB::table('subscription_payments')->whereNull('internal_reference')->count());

        $this->artisan('nutrilife:payment-references')->assertSuccessful();

        $refs = DB::table('payments')->orderBy('id')->pluck('internal_reference')
            ->merge(DB::table('subscription_payments')->orderBy('id')->pluck('internal_reference'));
        $this->assertSame(['EFE-20260901', 'TRA-20260903', 'OTR-20260904', 'SIM-20260901', 'OTR-20261001'], $refs->map(fn ($r) => substr($r, 0, 12))->all());
        $this->assertSame(5, $refs->unique()->count());
        $this->assertSame($before, $snapshot(), 'Montos, fechas, estados, relaciones y referencias externas intactos');

        // Idempotente: una segunda pasada no cambia nada.
        $this->artisan('nutrilife:payment-references')->expectsOutput('Todos los pagos ya tienen referencia interna.')->assertSuccessful();
        $this->assertSame($refs->all(), DB::table('payments')->orderBy('id')->pluck('internal_reference')->merge(DB::table('subscription_payments')->orderBy('id')->pluck('internal_reference'))->all());
    }

    public function test_platform_charges_get_references_and_admin_can_search_them(): void
    {
        PlanCheckout::purchase($this->user, SubscriptionPlan::where('name', 'Consultorio')->first());
        $charge = SubscriptionPayment::latest('id')->first();
        $this->assertStringStartsWith('SIM-20261022-', $charge->internal_reference);

        $admin = User::factory()->create();
        $admin->forceFill(['role_id' => \App\Models\Role::ADMIN])->save();

        $this->actingAs($admin)->get('/admin/cobros?q='.$charge->internal_reference)->assertOk()
            ->assertSee($charge->internal_reference)
            ->assertViewHas('payments', fn ($page) => $page->total() === 1);

        $this->actingAs($this->user->fresh())->get('/perfil')->assertOk()->assertSee($charge->internal_reference);
    }

    public function test_clinic_sees_references_of_its_team_only(): void
    {
        $clinic = $this->account('Consultorio');
        $member = User::factory()->create(['name' => 'Lic. Andrea']);
        $member->forceFill(['clinic_id' => $clinic->id])->save();
        $memberPatient = $this->patientFor($member, 'Sofía');

        $teamPayment = Payment::create(['user_id' => $member->id, 'patient_id' => $memberPatient->id, 'amount' => 700, 'paid_at' => now(), 'concept' => 'Consulta', 'payment_method' => 'Transferencia', 'status' => 'Pendiente', 'reference' => 'SPEI-1']);
        $this->store('Efectivo');
        $foreign = Payment::latest('id')->first();

        $this->actingAs($clinic)->get('/clinica/pagos')->assertOk()
            ->assertSee($teamPayment->internal_reference)->assertSee('Lic. Andrea')
            ->assertDontSee($foreign->internal_reference);

        $this->actingAs($clinic)->get('/clinica/pagos?q='.$foreign->internal_reference)->assertOk()
            ->assertViewHas('payments', fn ($page) => $page->total() === 0);

        // Solo lectura: la clínica no edita ni completa pagos de su equipo.
        $this->actingAs($clinic)->get("/pagos/{$teamPayment->id}")->assertNotFound();
        $this->actingAs($clinic)->patch("/pagos/{$teamPayment->id}/completar")->assertNotFound();
        $this->actingAs($member)->get('/clinica/pagos')->assertForbidden();
    }
}
