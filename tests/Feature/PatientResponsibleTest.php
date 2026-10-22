<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Nutriólogo responsable del paciente (patients.user_id) en clínicas:
 * ficha, cambio desde la edición y nutriólogo de cada cita en /citas.
 */
class PatientResponsibleTest extends TestCase
{
    use RefreshDatabase;

    private User $clinic;

    private User $andrea;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Esencial' => 249, 'Pro' => 399, 'Consultorio' => 599] as $name => $price) {
            SubscriptionPlan::create(['name' => $name, 'price' => $price, 'billing_period' => 'mensual', 'active' => true]);
        }

        $this->clinic = $this->account('Consultorio', 'Clínica Vida Sana');
        $this->andrea = $this->member($this->clinic, 'Lic. Andrea Solís');
    }

    private function account(string $plan, string $name = 'Nutrióloga'): User
    {
        $user = User::factory()->create(['name' => $name]);

        Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => SubscriptionPlan::where('name', $plan)->value('id'),
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now()->subDays(10)->toDateString(),
            'current_period_ends_at' => now()->addDays(20)->toDateString(),
        ]);

        return $user;
    }

    private function member(User $clinic, string $name): User
    {
        $member = User::factory()->create(['name' => $name]);
        $member->forceFill(['clinic_id' => $clinic->id])->save();

        return $member->fresh();
    }

    private function patientFor(User $user, string $first = 'Hugo', string $email = 'hugo@x.test'): Patient
    {
        return Patient::create(['user_id' => $user->id, 'first_name' => $first, 'last_name' => 'Olvera Campos', 'email' => $email, 'active' => true]);
    }

    private function updatePayload(Patient $patient, array $extra = []): array
    {
        return $extra + [
            'first_name' => $patient->first_name,
            'last_name' => $patient->last_name,
            'email' => $patient->email,
            'active' => 1,
        ];
    }

    public function test_patient_record_shows_the_responsible_nutritionist(): void
    {
        $hugo = $this->patientFor($this->clinic);
        $sofia = $this->patientFor($this->andrea, 'Sofía', 'sofia@x.test');

        $this->actingAs($this->clinic)->get(route('pacientes.show', $hugo))->assertOk()
            ->assertSee('Nutriólogo responsable')->assertSee('Clínica Vida Sana')->assertSee('Cuenta principal de la clínica');

        // La clínica también abre el expediente de un paciente de su subperfil
        $this->actingAs($this->clinic)->get(route('pacientes.show', $sofia))->assertOk()
            ->assertSee('Lic. Andrea Solís')->assertSee('Subperfil de la clínica')
            ->assertSee('Paciente de Lic. Andrea Solís')
            ->assertDontSee(route('evaluaciones.create', $sofia));

        $this->actingAs($this->andrea)->get(route('pacientes.show', $sofia))->assertOk()
            ->assertSee('Nutriólogo responsable')->assertSee(route('evaluaciones.create', $sofia));

        // Separación: el subperfil no ve pacientes de la cuenta principal; otra clínica tampoco.
        $this->actingAs($this->andrea)->get(route('pacientes.show', $hugo))->assertForbidden();
        $this->actingAs($this->account('Consultorio'))->get(route('pacientes.show', $sofia))->assertForbidden();

        // Nutriólogo individual: sin el campo de clínica.
        $solo = $this->account('Pro');
        $this->actingAs($solo)->get(route('pacientes.show', $this->patientFor($solo, 'Lalo', 'lalo@x.test')))->assertOk()
            ->assertDontSee('Nutriólogo responsable');
    }

    public function test_clinic_owner_can_reassign_a_patient_and_open_items_move_with_it(): void
    {
        $hugo = $this->patientFor($this->clinic);
        $future = Appointment::create(['user_id' => $this->clinic->id, 'patient_id' => $hugo->id, 'appointment_at' => now()->addDays(2), 'status' => 'Pendiente', 'reason' => 'Seguimiento']);
        $past = Appointment::create(['user_id' => $this->clinic->id, 'patient_id' => $hugo->id, 'appointment_at' => now()->subDays(5), 'status' => 'Completada', 'reason' => 'Inicial']);
        $consultation = Consultation::create(['user_id' => $this->clinic->id, 'patient_id' => $hugo->id, 'consultation_at' => now()->subDays(5), 'reason' => 'Inicial']);

        $this->actingAs($this->clinic)->get(route('pacientes.edit', $hugo))->assertOk()
            ->assertSee('name="nutritionist_id"', false)
            ->assertSee('Lic. Andrea Solís');

        $this->actingAs($this->clinic)->put(route('pacientes.update', $hugo), $this->updatePayload($hugo, ['nutritionist_id' => $this->andrea->id]))
            ->assertRedirect(route('pacientes.show', $hugo))
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'Ahora lo atiende Lic. Andrea Solís'));

        $this->assertSame($this->andrea->id, $hugo->fresh()->user_id);
        $this->assertSame($this->andrea->id, $future->fresh()->user_id, 'La cita próxima pasa a su agenda');
        $this->assertSame($this->clinic->id, $past->fresh()->user_id, 'El historial se queda con quien lo registró');
        $this->assertSame($this->clinic->id, $consultation->fresh()->user_id);
        $this->assertTrue(AuditLog::where('action', 'patient.reassigned')->where('subject_id', $hugo->id)->exists());

        // Ahora Andrea lo atiende y la clínica lo sigue viendo y lo puede regresar.
        $this->actingAs($this->andrea)->get(route('pacientes.show', $hugo))->assertOk();
        $this->actingAs($this->clinic)->put(route('pacientes.update', $hugo), $this->updatePayload($hugo->fresh(), ['nutritionist_id' => $this->clinic->id]))
            ->assertRedirect(route('pacientes.show', $hugo));
        $this->assertSame($this->clinic->id, $hugo->fresh()->user_id);
    }

    public function test_reassignment_is_limited_to_the_clinic_owner_and_its_active_team(): void
    {
        $hugo = $this->patientFor($this->clinic);
        $sofia = $this->patientFor($this->andrea, 'Sofía', 'sofia@x.test');
        $foreign = $this->account('Pro');
        $disabled = $this->member($this->clinic, 'Subperfil Desactivado');
        $disabled->forceFill(['account_status' => User::ACCOUNT_DISABLED])->save();

        $this->actingAs($this->clinic)->get(route('pacientes.edit', $hugo))->assertOk()->assertDontSee('Subperfil Desactivado');

        $this->actingAs($this->clinic)->put(route('pacientes.update', $hugo), $this->updatePayload($hugo, ['nutritionist_id' => $foreign->id]))
            ->assertSessionHasErrors('nutritionist_id');
        $this->actingAs($this->clinic)->put(route('pacientes.update', $hugo), $this->updatePayload($hugo, ['nutritionist_id' => $disabled->id]))
            ->assertSessionHasErrors('nutritionist_id');
        $this->assertSame($this->clinic->id, $hugo->fresh()->user_id);

        // El subperfil no tiene el selector ni puede mandarlo a mano.
        $this->actingAs($this->andrea)->get(route('pacientes.edit', $sofia))->assertOk()
            ->assertDontSee('name="nutritionist_id"', false)
            ->assertSee('Solo la cuenta principal de la clínica puede cambiarlo.');
        $this->actingAs($this->andrea)->put(route('pacientes.update', $sofia), $this->updatePayload($sofia, ['nutritionist_id' => $this->clinic->id]))
            ->assertSessionHasErrors('nutritionist_id');
        $this->assertSame($this->andrea->id, $sofia->fresh()->user_id);

        // Correo repetido en los pacientes del nuevo nutriólogo: no se cambia.
        $this->patientFor($this->andrea, 'Otro Hugo', 'hugo@x.test');
        $this->actingAs($this->clinic)->put(route('pacientes.update', $hugo), $this->updatePayload($hugo, ['nutritionist_id' => $this->andrea->id]))
            ->assertSessionHasErrors('email');
        $this->assertSame($this->clinic->id, $hugo->fresh()->user_id);
    }

    public function test_clinic_calendar_shows_the_nutritionist_of_each_appointment(): void
    {
        $sofia = $this->patientFor($this->andrea, 'Sofía', 'sofia@x.test');
        $hugo = $this->patientFor($this->clinic);
        $theirs = Appointment::create(['user_id' => $this->andrea->id, 'patient_id' => $sofia->id, 'appointment_at' => now()->addDay(), 'status' => 'Pendiente', 'reason' => 'Control Sofía']);
        Appointment::create(['user_id' => $this->clinic->id, 'patient_id' => $hugo->id, 'appointment_at' => now()->addDay(), 'status' => 'Pendiente', 'reason' => 'Control Hugo']);

        $response = $this->actingAs($this->clinic)->get('/citas')->assertOk()
            ->assertSee('Control Sofía')->assertSee('Control Hugo')
            ->assertSee('id="calendarNutritionist"', false)
            ->assertViewHas('isClinic', true);
        $response->assertSee('"nutritionist":"Lic. Andrea Sol', false);

        // Cancelar sigue siendo solo de quien agendó la cita.
        $this->actingAs($this->clinic)->patch(route('citas.cancel', $theirs))->assertForbidden();

        // El subperfil solo ve sus propias citas, sin el filtro de equipo.
        $this->actingAs($this->andrea)->get('/citas')->assertOk()
            ->assertSee('Control Sofía')->assertDontSee('Control Hugo')
            ->assertDontSee('id="calendarNutritionist"', false);
    }
}
