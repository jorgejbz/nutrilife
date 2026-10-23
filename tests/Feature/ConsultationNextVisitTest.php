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
 * La próxima consulta capturada al registrar una consulta debe quedar como
 * cita Pendiente para verse en el calendario, pacientes y dashboard.
 */
class ConsultationNextVisitTest extends TestCase
{
    use RefreshDatabase;

    private function account(): User
    {
        $plan = SubscriptionPlan::create(['name' => 'Pro', 'price' => 399, 'billing_period' => 'mensual', 'active' => true]);
        $user = User::factory()->create();

        Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now()->subDays(10)->toDateString(),
            'current_period_ends_at' => now()->addDays(20)->toDateString(),
        ]);

        return $user;
    }

    private function consultation(User $user, Patient $patient, array $extra = [])
    {
        return $this->actingAs($user)->post(route('consultas.store'), array_merge([
            'patient_id' => $patient->id,
            'consultation_at' => now()->subHour()->format('Y-m-d H:i:s'),
            'reason' => 'Control',
        ], $extra));
    }

    public function test_next_visit_creates_a_pending_appointment(): void
    {
        $user = $this->account();
        $patient = Patient::create(['user_id' => $user->id, 'first_name' => 'Ana', 'last_name' => 'Prueba', 'email' => 'ana@x.test', 'active' => true]);
        $date = now()->addWeek()->toDateString();

        $this->consultation($user, $patient, ['next_visit' => $date, 'next_visit_time' => '11:30'])
            ->assertRedirect(route('consultas.index'))
            ->assertSessionHasNoErrors();

        $appointment = Appointment::where('patient_id', $patient->id)->sole();
        $this->assertSame('Pendiente', $appointment->status);
        $this->assertSame($date.' 11:30', $appointment->appointment_at->format('Y-m-d H:i'));
        $this->assertSame($user->id, $appointment->user_id);
    }

    public function test_next_visit_does_not_duplicate_an_open_appointment_that_day(): void
    {
        $user = $this->account();
        $patient = Patient::create(['user_id' => $user->id, 'first_name' => 'Luis', 'last_name' => 'Prueba', 'email' => 'luis@x.test', 'active' => true]);
        $date = now()->addWeek()->toDateString();
        Appointment::create(['user_id' => $user->id, 'patient_id' => $patient->id, 'appointment_at' => $date.' 16:00', 'status' => 'Confirmada']);

        $this->consultation($user, $patient, ['next_visit' => $date])->assertSessionHasNoErrors();

        $this->assertSame(1, Appointment::where('patient_id', $patient->id)->count());
    }

    public function test_without_next_visit_no_appointment_is_created(): void
    {
        $user = $this->account();
        $patient = Patient::create(['user_id' => $user->id, 'first_name' => 'Eva', 'last_name' => 'Prueba', 'email' => 'eva@x.test', 'active' => true]);

        $this->consultation($user, $patient)->assertSessionHasNoErrors();

        $this->assertSame(0, Appointment::count());
    }
}
