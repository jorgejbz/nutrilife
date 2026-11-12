<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Auditoría 2026-11-12: la web permitía agendar dos citas encimadas
 * (10:00 y 10:30) y los mensajes de validación salían en inglés.
 */
class AppointmentOverlapTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = SubscriptionPlan::create(['name' => 'Esencial', 'price' => 249, 'billing_period' => 'mensual', 'active' => true]);
        $this->user = User::factory()->create();
        Subscription::create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now()->subDays(5)->toDateString(),
            'current_period_ends_at' => now()->addDays(25)->toDateString(),
        ]);
        $this->patient = Patient::create(['user_id' => $this->user->id, 'first_name' => 'Ana', 'last_name' => 'Prueba', 'email' => 'ana@x.test', 'active' => true]);
    }

    private function book(string $at, string $status = 'Pendiente')
    {
        return $this->actingAs($this->user)->from(route('citas.create'))->post(route('citas.store'), [
            'patient_id' => $this->patient->id,
            'appointment_at' => $at,
            'status' => $status,
        ]);
    }

    public function test_overlapping_open_appointment_is_rejected(): void
    {
        $day = now()->addDays(3)->format('Y-m-d');

        $this->book("$day 10:00")->assertSessionHasNoErrors();
        $this->book("$day 10:30")->assertSessionHasErrors('appointment_at');
        $this->book("$day 11:00")->assertSessionHasNoErrors(); // justo al terminar la anterior

        $this->assertSame(2, Appointment::count());
    }

    public function test_cancelled_appointments_do_not_block_the_slot(): void
    {
        $day = now()->addDays(3)->format('Y-m-d');

        Appointment::create(['user_id' => $this->user->id, 'patient_id' => $this->patient->id, 'appointment_at' => "$day 10:00", 'status' => 'Cancelada']);

        $this->book("$day 10:00")->assertSessionHasNoErrors();
    }

    public function test_validation_messages_are_in_spanish(): void
    {
        $this->actingAs($this->user)->from(route('pacientes.create'))
            ->post(route('pacientes.store'), ['email' => 'no-es-correo'])
            ->assertSessionHasErrors([
                'first_name' => 'Nombre es obligatorio.',
                'email' => 'Correo electrónico debe ser un correo electrónico válido.',
            ]);
    }
}
