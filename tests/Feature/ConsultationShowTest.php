<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * «Ver consulta» abre el detalle (consultas.show) y «Ver consultas» de la
 * ficha del paciente filtra el historial (?paciente=), con permisos.
 */
class ConsultationShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SubscriptionPlan::create(['name' => 'Pro', 'price' => 399, 'billing_period' => 'mensual', 'active' => true]);
    }

    private function account(): User
    {
        $user = User::factory()->create();

        Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => SubscriptionPlan::where('name', 'Pro')->value('id'),
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now()->subDays(10)->toDateString(),
            'current_period_ends_at' => now()->addDays(20)->toDateString(),
        ]);

        return $user;
    }

    private function patientFor(User $user, string $first): Patient
    {
        return Patient::create(['user_id' => $user->id, 'first_name' => $first, 'last_name' => 'Prueba', 'email' => strtolower($first).uniqid().'@x.test', 'active' => true]);
    }

    private function consultation(User $user, Patient $patient, string $reason): Consultation
    {
        return Consultation::create([
            'user_id' => $user->id,
            'patient_id' => $patient->id,
            'consultation_at' => now()->subDay(),
            'reason' => $reason,
            'observations' => 'Observación de '.$reason,
            'recommendations' => 'Recomendación de '.$reason,
        ]);
    }

    public function test_view_button_links_to_the_consultation_detail(): void
    {
        $user = $this->account();
        $consultation = $this->consultation($user, $this->patientFor($user, 'Ana'), 'Control mensual');

        $this->actingAs($user)->get(route('consultas.index'))->assertOk()
            ->assertSee('href="'.route('consultas.show', $consultation).'"', false)
            ->assertDontSee('consultation-view-disabled');

        $this->actingAs($user)->get(route('consultas.show', $consultation))->assertOk()
            ->assertSee('Control mensual')
            ->assertSee('Observación de Control mensual')
            ->assertSee('Recomendación de Control mensual')
            ->assertSee(route('consultas.index', ['paciente' => $consultation->patient_id]), false);
    }

    public function test_other_accounts_cannot_open_a_consultation_and_missing_ones_are_404(): void
    {
        $owner = $this->account();
        $other = $this->account();
        $consultation = $this->consultation($owner, $this->patientFor($owner, 'Ana'), 'Privada');

        $this->actingAs($other)->get(route('consultas.show', $consultation))->assertForbidden();
        $this->actingAs($owner)->get('/consultas/999999')->assertNotFound();

        $consultation->delete();
        $this->actingAs($owner)->get(route('consultas.show', $consultation->id))->assertNotFound();
    }

    public function test_patient_filter_shows_only_that_patient_and_ignores_foreign_ids(): void
    {
        $user = $this->account();
        $ana = $this->patientFor($user, 'Ana');
        $luis = $this->patientFor($user, 'Luis');
        $this->consultation($user, $ana, 'Motivo Ana');
        $this->consultation($user, $luis, 'Motivo Luis');

        $this->actingAs($user)->get(route('consultas.index', ['paciente' => $ana->id]))->assertOk()
            ->assertSee('Motivo Ana')
            ->assertDontSee('Motivo Luis')
            ->assertSee('Ver todas');

        // Paciente sin consultas: estado vacío claro
        $eva = $this->patientFor($user, 'Eva');
        $this->actingAs($user)->get(route('consultas.index', ['paciente' => $eva->id]))->assertOk()
            ->assertSee('Este paciente aún no tiene consultas');

        // Paciente de otra cuenta: no filtra ni filtra datos ajenos
        $other = $this->account();
        $foreign = $this->patientFor($other, 'Ajeno');
        $this->consultation($other, $foreign, 'Motivo ajeno');

        $this->actingAs($user)->get(route('consultas.index', ['paciente' => $foreign->id]))->assertOk()
            ->assertSee('no pertenece a tu consultorio')
            ->assertDontSee('Motivo ajeno')
            ->assertSee('Motivo Ana');
    }

    private function withNextVisit(User $user): array
    {
        $patient = $this->patientFor($user, 'Manuel');
        $date = now()->addMonth()->toDateString();
        $consultation = $this->consultation($user, $patient, 'Seguimiento');
        $consultation->update(['next_visit' => $date]);
        $appointment = Appointment::create(['user_id' => $user->id, 'patient_id' => $patient->id, 'appointment_at' => $date.' 09:00', 'reason' => 'Seguimiento', 'status' => 'Pendiente']);

        return [$consultation, $appointment];
    }

    public function test_next_appointment_status_is_shown_and_can_be_cancelled_from_the_detail(): void
    {
        $user = $this->account();
        [$consultation, $appointment] = $this->withNextVisit($user);

        $this->actingAs($user)->get(route('consultas.show', $consultation))->assertOk()
            ->assertSee('Cita de la próxima consulta')
            ->assertSee('consultation-status--pendiente', false)
            ->assertSee('action="'.route('citas.cancel', $appointment).'"', false)
            ->assertSee('action="'.route('citas.destroy', $appointment).'"', false);

        // Cancelar desde el detalle regresa al detalle y el estado cambia
        $this->actingAs($user)->patch(route('citas.cancel', $appointment), ['consulta' => $consultation->id])
            ->assertRedirect(route('consultas.show', $consultation))
            ->assertSessionHas('success');

        $this->assertSame('Cancelada', $appointment->fresh()->status);

        $this->actingAs($user)->get(route('consultas.show', $consultation))->assertOk()
            ->assertSee('consultation-status--cancelada', false)
            ->assertDontSee('action="'.route('citas.cancel', $appointment).'"', false) // ya no se puede cancelar
            ->assertSee('action="'.route('citas.destroy', $appointment).'"', false);  // pero sí eliminar

        // Eliminar desde el detalle
        $this->actingAs($user)->delete(route('citas.destroy', $appointment), ['consulta' => $consultation->id])
            ->assertRedirect(route('consultas.show', $consultation));

        $this->assertNull($appointment->fresh());
        $this->actingAs($user)->get(route('consultas.show', $consultation))->assertOk()
            ->assertSee('No hay cita en el calendario para el');
    }

    public function test_appointments_with_payments_cannot_be_deleted_and_foreign_accounts_are_blocked(): void
    {
        $user = $this->account();
        [$consultation, $appointment] = $this->withNextVisit($user);
        Payment::create(['user_id' => $user->id, 'patient_id' => $appointment->patient_id, 'appointment_id' => $appointment->id, 'amount' => 500, 'paid_at' => now(), 'payment_method' => 'Efectivo', 'status' => 'Pagado']);

        $this->actingAs($user)->get(route('consultas.show', $consultation))->assertOk()
            ->assertSee('No se puede eliminar')
            ->assertDontSee('action="'.route('citas.destroy', $appointment).'"', false);

        $this->actingAs($user)->delete(route('citas.destroy', $appointment), ['consulta' => $consultation->id])
            ->assertRedirect(route('consultas.show', $consultation))
            ->assertSessionHas('error');
        $this->assertNotNull($appointment->fresh());

        // Otra cuenta: no puede cancelar, y una consulta ajena no cambia el regreso
        $other = $this->account();
        $this->actingAs($other)->patch(route('citas.cancel', $appointment), ['consulta' => $consultation->id])
            ->assertForbidden();

        [$otherConsultation, $otherAppointment] = $this->withNextVisit($other);
        $this->actingAs($user)->patch(route('citas.cancel', $appointment), ['consulta' => $otherConsultation->id])
            ->assertRedirect(route('citas.index', ['dia' => $appointment->appointment_at->toDateString()]));
    }

    public function test_patient_profile_links_to_its_consultations(): void
    {
        $user = $this->account();
        $ana = $this->patientFor($user, 'Ana');

        $this->actingAs($user)->get(route('pacientes.show', $ana))->assertOk()
            ->assertSee('href="'.route('consultas.index', ['paciente' => $ana->id]).'"', false)
            ->assertSee('Ver consultas');
    }
}
