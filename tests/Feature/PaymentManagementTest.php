<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /pagos: detalle, edición, filtros y cambio de estado protegido.
 */
class PaymentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = SubscriptionPlan::create(['name' => 'Pro', 'price' => 399, 'billing_period' => 'mensual', 'active' => true]);
        $this->user = $this->nutritionist($plan);
        $this->patient = Patient::create(['user_id' => $this->user->id, 'first_name' => 'Lucía', 'last_name' => 'Ramos', 'email' => 'lucia@x.test', 'active' => true]);
    }

    private function nutritionist(SubscriptionPlan $plan): User
    {
        $user = User::factory()->create();
        Subscription::create(['user_id' => $user->id, 'subscription_plan_id' => $plan->id, 'status' => 'activa', 'starts_at' => now()->subDay()->toDateString(), 'current_period_ends_at' => now()->addMonth()->toDateString()]);

        return $user;
    }

    private function payment(string $status = 'Pendiente', ?User $owner = null, ?Patient $patient = null): Payment
    {
        return Payment::create([
            'user_id' => ($owner ?? $this->user)->id,
            'patient_id' => ($patient ?? $this->patient)->id,
            'amount' => 650,
            'paid_at' => now()->subDays(2),
            'concept' => 'Consulta de seguimiento',
            'payment_method' => 'Transferencia',
            'status' => $status,
            'reference' => 'SPEI-123',
        ]);
    }

    public function test_pending_payment_can_be_marked_as_completed_by_its_owner(): void
    {
        $payment = $this->payment();

        $this->actingAs($this->user)->get('/pagos')->assertOk()->assertSee('Marcar como completado')->assertSee('Pendiente');

        $this->actingAs($this->user)->from('/pagos')->patch("/pagos/{$payment->id}/completar")->assertRedirect('/pagos');

        $payment->refresh();
        $this->assertSame('Pagado', $payment->status);
        $this->assertSame($this->user->id, (int) $payment->status_changed_by);
        $this->assertNotNull($payment->status_changed_at);
        $this->assertTrue($payment->paid_at->isToday(), 'La fecha de pago pasa a ser cuando se confirma');
        $this->assertTrue(AuditLog::where('action', 'payment.completed')->where('subject_id', $payment->id)->exists());

        $this->actingAs($this->user)->get('/pagos')->assertDontSee('Marcar como completado');
    }

    public function test_only_pending_payments_change_and_cancel_works(): void
    {
        $paid = $this->payment('Pagado');
        $this->actingAs($this->user)->patch("/pagos/{$paid->id}/cancelar")->assertSessionHas('error');
        $this->assertSame('Pagado', $paid->fresh()->status);

        $pending = $this->payment();
        $this->actingAs($this->user)->patch("/pagos/{$pending->id}/cancelar")->assertSessionHas('success');
        $this->assertSame('Cancelado', $pending->fresh()->status);
    }

    public function test_other_users_cannot_see_edit_or_complete_a_payment(): void
    {
        $payment = $this->payment();
        $other = $this->nutritionist(SubscriptionPlan::first());

        $this->actingAs($other)->get("/pagos/{$payment->id}")->assertNotFound();
        $this->actingAs($other)->get("/pagos/{$payment->id}/edit")->assertNotFound();
        $this->actingAs($other)->put("/pagos/{$payment->id}", ['amount' => 1])->assertNotFound();
        $this->actingAs($other)->patch("/pagos/{$payment->id}/completar")->assertNotFound();

        $this->assertSame('Pendiente', $payment->fresh()->status);
    }

    public function test_edit_updates_fields_but_never_the_status_or_patient(): void
    {
        $payment = $this->payment();
        $otherPatient = Patient::create(['user_id' => $this->user->id, 'first_name' => 'Otro', 'last_name' => 'P', 'email' => 'otro@x.test', 'active' => true]);

        $this->actingAs($this->user)->get("/pagos/{$payment->id}/edit")->assertOk()->assertSee('Editar pago');

        $this->actingAs($this->user)->put("/pagos/{$payment->id}", [
            'amount' => '700.50',
            'paid_at' => now()->subDay()->format('Y-m-d H:i'),
            'concept' => 'Consulta inicial',
            'payment_method' => 'Efectivo',
            'reference' => '',
            'notes' => 'Ajuste',
            'status' => 'Pagado',
            'patient_id' => $otherPatient->id,
        ])->assertRedirect("/pagos/{$payment->id}");

        $payment->refresh();
        $this->assertSame('700.50', $payment->amount);
        $this->assertSame('Consulta inicial', $payment->concept);
        $this->assertSame('Pendiente', $payment->status, 'El estado solo cambia con sus acciones');
        $this->assertSame($this->patient->id, $payment->patient_id);

        $this->actingAs($this->user)->get("/pagos/{$payment->id}")->assertOk()->assertSee('Consulta inicial')->assertSee('Pago editado');
    }

    public function test_filters_and_search(): void
    {
        $this->payment('Pendiente');
        $paid = $this->payment('Pagado');
        $paid->update(['concept' => 'Paquete mensual', 'reference' => 'FOLIO-999']);

        $this->actingAs($this->user)->get('/pagos?estado=Pagado')->assertSee('Paquete mensual')->assertDontSee('Consulta de seguimiento');
        $this->actingAs($this->user)->get('/pagos?q=FOLIO-999')->assertSee('Paquete mensual')->assertDontSee('Consulta de seguimiento');
        $this->actingAs($this->user)->get('/pagos?q=Lucía Ramos')->assertSee('Paquete mensual')->assertSee('Consulta de seguimiento');
    }

    public function test_clinic_cannot_change_payments_of_its_members(): void
    {
        $clinicPlan = SubscriptionPlan::create(['name' => 'Consultorio', 'price' => 599, 'billing_period' => 'mensual', 'active' => true]);
        $clinic = $this->nutritionist($clinicPlan);
        $member = User::factory()->create();
        $member->forceFill(['clinic_id' => $clinic->id])->save();
        $patient = Patient::create(['user_id' => $member->id, 'first_name' => 'M', 'last_name' => 'P', 'email' => 'm@x.test', 'active' => true]);
        $payment = $this->payment('Pendiente', $member, $patient);

        $this->actingAs($clinic)->patch("/pagos/{$payment->id}/completar")->assertNotFound();
        $this->actingAs($member)->patch("/pagos/{$payment->id}/completar")->assertRedirect();
        $this->assertSame('Pagado', $payment->fresh()->status);
    }
}
