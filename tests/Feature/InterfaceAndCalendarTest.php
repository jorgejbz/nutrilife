<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Accesos por icono, consultas de la clínica, filtros rápidos de la agenda
 * y detalle por día del calendario de /citas.
 */
class InterfaceAndCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Esencial' => 249, 'Pro' => 399, 'Consultorio' => 599] as $name => $price) {
            SubscriptionPlan::create(['name' => $name, 'price' => $price, 'billing_period' => 'mensual', 'active' => true]);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function account(string $plan = 'Pro'): User
    {
        $user = User::factory()->create();

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

    private function patientFor(User $user, string $first): Patient
    {
        return Patient::create(['user_id' => $user->id, 'first_name' => $first, 'last_name' => 'Prueba', 'email' => strtolower($first).uniqid().'@x.test', 'active' => true]);
    }

    private function appointment(User $user, Patient $patient, Carbon $at, string $reason, string $status = 'Pendiente'): Appointment
    {
        return Appointment::create(['user_id' => $user->id, 'patient_id' => $patient->id, 'appointment_at' => $at, 'reason' => $reason, 'status' => $status]);
    }

    public function test_dashboard_and_consultations_icons_link_to_the_existing_create_forms(): void
    {
        $user = $this->account();

        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertSee('href="'.route('pacientes.create').'" class="dashboard-chart-card__icon is-action"', false)
            ->assertSee('aria-label="Registrar paciente nuevo"', false);

        $this->actingAs($user)->get('/consultas')->assertOk()
            ->assertSee('href="'.route('consultas.create').'" class="consultations-card-header__action"', false)
            ->assertSee('Nueva consulta');

        $this->actingAs($user)->get(route('pacientes.create'))->assertOk();
        $this->actingAs($user)->get(route('consultas.create'))->assertOk();
    }

    public function test_evaluation_button_has_hover_and_focus_styles(): void
    {
        $css = file_get_contents(public_path('css/admin/components/interface.css'));

        $this->assertStringContainsString('.progress-add-button:hover', $css);
        $this->assertStringContainsString('.progress-add-button:focus-visible', $css);
        $this->assertStringContainsString('.nl-input select option', $css);
    }

    public function test_clinic_consultations_kpi_links_to_a_clinic_wide_consultation_list(): void
    {
        Carbon::setTestNow('2026-10-15 12:00:00');

        $clinic = $this->account('Consultorio');
        $member = $this->member($clinic, 'Nutrióloga Equipo');
        $ana = $this->patientFor($member, 'Ana');
        $own = $this->patientFor($clinic, 'Beto');

        $other = $this->account('Consultorio');
        $foreignPatient = $this->patientFor($other, 'Ximena');

        Consultation::create(['user_id' => $member->id, 'patient_id' => $ana->id, 'consultation_at' => '2026-10-03 10:00', 'reason' => 'Revisión octubre']);
        Consultation::create(['user_id' => $clinic->id, 'patient_id' => $own->id, 'consultation_at' => '2026-10-05 10:00', 'reason' => 'Control principal']);
        Consultation::create(['user_id' => $member->id, 'patient_id' => $ana->id, 'consultation_at' => '2026-09-20 10:00', 'reason' => 'Revisión septiembre']);
        Consultation::create(['user_id' => $other->id, 'patient_id' => $foreignPatient->id, 'consultation_at' => '2026-10-04 10:00', 'reason' => 'De otra clínica']);

        $link = route('clinic.consultations.index', ['desde' => '2026-10-01']);

        $this->actingAs($clinic)->get('/clinica')->assertOk()
            ->assertSee('href="'.e($link).'" class="nl-kpi"', false)
            ->assertViewHas('totals', fn ($totals) => $totals['consultations_month'] === 2);

        // El enlace del indicador muestra las mismas 2 consultas del mes.
        $this->actingAs($clinic)->get($link)->assertOk()
            ->assertViewHas('consultations', fn ($page) => $page->total() === 2)
            ->assertSee('Revisión octubre')->assertSee('Control principal')
            ->assertDontSee('Revisión septiembre')->assertDontSee('De otra clínica');

        $this->actingAs($clinic)->get('/clinica/consultas?nutriologo='.$member->id)->assertOk()
            ->assertSee('Revisión septiembre')->assertDontSee('Control principal');

        // Nutriólogo de otra clínica en el filtro: se ignora (no filtra por ajenos).
        $this->actingAs($clinic)->get('/clinica/consultas?nutriologo='.$other->id)->assertOk()
            ->assertDontSee('De otra clínica');

        // Solo la cuenta principal de la clínica.
        $this->actingAs($member)->get('/clinica/consultas')->assertForbidden();
        $this->actingAs($this->account())->get('/clinica/consultas')->assertForbidden();
    }

    public function test_agenda_quick_day_filters_show_only_that_day_and_keep_other_filters(): void
    {
        Carbon::setTestNow('2026-10-22 09:00:00');

        $clinic = $this->account('Consultorio');
        $member = $this->member($clinic, 'Nutrióloga Agenda');
        $patient = $this->patientFor($member, 'Daniela');

        $this->appointment($member, $patient, Carbon::parse('2026-10-21 10:00'), 'Cita de ayer', 'Completada');
        $this->appointment($member, $patient, Carbon::parse('2026-10-22 23:30'), 'Cita de hoy noche');
        $this->appointment($member, $patient, Carbon::parse('2026-10-22 11:00'), 'Cita de hoy cancelada', 'Cancelada');
        $this->appointment($member, $patient, Carbon::parse('2026-10-23 00:15'), 'Cita de mañana');
        $this->appointment($member, $patient, Carbon::parse('2026-10-25 10:00'), 'Cita lejana');

        $this->actingAs($clinic)->get('/clinica/agenda?dia=ayer')->assertOk()
            ->assertSee('Cita de ayer')->assertDontSee('Cita de hoy noche')->assertDontSee('Cita de mañana')
            ->assertViewHas('filters', fn ($f) => $f['dia'] === 'ayer' && $f['desde'] === '2026-10-21' && $f['hasta'] === '2026-10-21');

        $this->actingAs($clinic)->get('/clinica/agenda?dia=hoy')->assertOk()
            ->assertSee('Cita de hoy noche')->assertSee('Cita de hoy cancelada')
            ->assertDontSee('Cita de ayer')->assertDontSee('Cita de mañana')->assertDontSee('Cita lejana');

        // El día rápido reemplaza desde/hasta anteriores y conserva el estado.
        $this->actingAs($clinic)->get('/clinica/agenda?dia=hoy&estado=Pendiente&desde=2026-01-01&hasta=2026-12-31')->assertOk()
            ->assertSee('Cita de hoy noche')->assertDontSee('Cita de hoy cancelada')->assertDontSee('Cita lejana');

        $this->actingAs($clinic)->get('/clinica/agenda?dia=manana&estado=Pendiente')->assertOk()
            ->assertSee('Cita de mañana')->assertDontSee('Cita de hoy noche')->assertDontSee('Cita lejana')
            ->assertSee('aria-current="true"', false)
            ->assertSee('href="'.e(route('clinic.agenda.index', ['estado' => 'Pendiente', 'dia' => 'hoy'])).'"', false);

        // Limpiar filtros = sin parámetros: de hoy en adelante, sin estado.
        $this->actingAs($clinic)->get('/clinica/agenda')->assertOk()
            ->assertSee('Limpiar filtros')
            ->assertSee('Cita de hoy noche')->assertSee('Cita de hoy cancelada')->assertSee('Cita de mañana')->assertSee('Cita lejana')
            ->assertDontSee('Cita de ayer')
            ->assertViewHas('filters', fn ($f) => $f['dia'] === null && $f['estado'] === null && $f['hasta'] === null)
            ->assertViewHas('hasFilters', false);

        // Valor inválido: se ignora.
        $this->actingAs($clinic)->get('/clinica/agenda?dia=pasado')->assertOk()
            ->assertViewHas('filters', fn ($f) => $f['dia'] === null);
    }

    public function test_calendar_opens_the_requested_day_with_only_own_appointments(): void
    {
        Carbon::setTestNow('2026-10-22 09:00:00');

        $user = $this->account();
        $patient = $this->patientFor($user, 'Marta');
        $mine = $this->appointment($user, $patient, Carbon::parse('2026-10-24 10:00'), 'Control mensual');

        $other = $this->account();
        $this->appointment($other, $this->patientFor($other, 'Ajena'), Carbon::parse('2026-10-24 12:00'), 'Cita ajena');

        $response = $this->actingAs($user)->get('/citas?dia=2026-10-24')->assertOk()
            ->assertViewHas('selectedDay', '2026-10-24')
            ->assertViewHas('today', '2026-10-22')
            ->assertSee('Control mensual')
            ->assertDontSee('Cita ajena');

        $response->assertSee(json_encode(route('pacientes.show', $patient)), false);
        $response->assertSee(json_encode(route('consultas.create', ['cita' => $mine->id])), false);

        $this->actingAs($user)->get('/citas?dia=2026-02-30')->assertOk()->assertViewHas('selectedDay', '2026-10-22');
        $this->actingAs($user)->get('/citas?dia=<script>')->assertOk()->assertViewHas('selectedDay', '2026-10-22');
    }

    public function test_cancelling_from_the_calendar_returns_to_that_day_and_respects_ownership(): void
    {
        $user = $this->account();
        $patient = $this->patientFor($user, 'Marta');
        $appointment = $this->appointment($user, $patient, now()->addDays(3)->setTime(10, 0), 'Control');

        $this->actingAs($user)->patch(route('citas.cancel', $appointment))
            ->assertRedirect(route('citas.index', ['dia' => $appointment->appointment_at->toDateString()]));
        $this->assertSame('Cancelada', $appointment->fresh()->status);

        $intruder = $this->account();
        $open = $this->appointment($user, $patient, now()->addDays(4), 'Otra');
        $this->actingAs($intruder)->patch(route('citas.cancel', $open))->assertForbidden();
        $this->actingAs($intruder)->delete(route('citas.destroy', $open))->assertForbidden();
        $this->assertSame('Pendiente', $open->fresh()->status);
    }

    public function test_confirming_from_the_calendar_returns_to_that_day_and_respects_ownership(): void
    {
        $user = $this->account();
        $patient = $this->patientFor($user, 'Marta');
        $appointment = $this->appointment($user, $patient, now()->addDays(3)->setTime(10, 0), 'Control');

        $this->actingAs($user)->patch(route('citas.confirm', $appointment))
            ->assertRedirect(route('citas.index', ['dia' => $appointment->appointment_at->toDateString()]))
            ->assertSessionHas('success');
        $this->assertSame('Confirmada', $appointment->fresh()->status);

        // Solo las pendientes se confirman: repetir la acción no cambia nada.
        $this->actingAs($user)->patch(route('citas.confirm', $appointment))
            ->assertRedirect(route('citas.index', ['dia' => $appointment->appointment_at->toDateString()]))
            ->assertSessionHas('error');
        $this->assertSame('Confirmada', $appointment->fresh()->status);

        $intruder = $this->account();
        $open = $this->appointment($user, $patient, now()->addDays(4), 'Otra');
        $this->actingAs($intruder)->patch(route('citas.confirm', $open))->assertForbidden();
        $this->assertSame('Pendiente', $open->fresh()->status);
    }

    public function test_consultation_form_preselects_an_own_open_appointment_only(): void
    {
        $user = $this->account();
        $patient = $this->patientFor($user, 'Marta');
        $appointment = $this->appointment($user, $patient, now()->addDay(), 'Control');

        $this->actingAs($user)->get(route('consultas.create', ['cita' => $appointment->id]))->assertOk()
            ->assertViewHas('selectedAppointment', fn ($selected) => $selected?->id === $appointment->id);

        $other = $this->account();
        $foreign = $this->appointment($other, $this->patientFor($other, 'Ajena'), now()->addDay(), 'Ajena');

        $this->actingAs($user)->get(route('consultas.create', ['cita' => $foreign->id]))->assertOk()
            ->assertViewHas('selectedAppointment', null);
    }

    public function test_clinic_patients_show_their_responsible_nutritionist_and_filter_by_it(): void
    {
        $clinic = $this->account('Consultorio');
        $andrea = $this->member($clinic, 'Lic. Andrea Solís');
        $diego = $this->member($clinic, 'Lic. Diego Navarro');
        $this->patientFor($andrea, 'Mónica');
        $this->patientFor($andrea, 'Sofía');
        $this->patientFor($diego, 'Rodrigo');
        $this->patientFor($clinic, 'Teresa');

        $this->actingAs($clinic)->get('/clinica/pacientes')->assertOk()
            ->assertViewHas('patientCounts', fn ($counts) => $counts[$andrea->id] === 2 && $counts[$diego->id] === 1 && $counts[$clinic->id] === 1)
            ->assertSee('Subperfil de la clínica')->assertSee('Cuenta principal')
            ->assertSee('<span class="nl-owner__avatar" aria-hidden="true">AS</span>', false)
            ->assertSee('href="'.e(route('clinic.patients.index', ['nutriologo' => $andrea->id])).'"', false);

        $this->actingAs($clinic)->get('/clinica/pacientes?nutriologo='.$andrea->id)->assertOk()
            ->assertSee('Mónica')->assertSee('Sofía')->assertDontSee('Rodrigo')->assertDontSee('Teresa');
    }
}
