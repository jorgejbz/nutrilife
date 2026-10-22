<?php

namespace App\Services;

use App\Http\Controllers\AppointmentController;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Indicadores del panel del consultorio (ClinicDashboardController).
 *
 * Todo sale de la BD real, limitado a la organización: la cuenta principal
 * (que también atiende) y sus subperfiles (users.clinic_id). Los pacientes
 * se cuentan una sola vez: cada expediente tiene un único nutriólogo
 * responsable (patients.user_id) y los eliminados (soft delete) no cuentan.
 */
class ClinicStats
{
    /** @var list<int> */
    private array $ids;

    public function __construct(private User $clinic)
    {
        $this->ids = $clinic->clinicNutritionistIds();
    }

    /** @return list<int> */
    public function nutritionistIds(): array
    {
        return $this->ids;
    }

    /**
     * Profesionales de la clínica con sus conteos (la cuenta principal
     * primero).
     */
    public function nutritionists(): Collection
    {
        return User::whereIn('id', $this->ids)
            ->withCount([
                'patients',
                'patients as active_patients_count' => fn ($query) => $query->where('active', true),
                'appointments as today_appointments_count' => fn ($query) => $query->whereDate('appointment_at', today()),
                'appointments as upcoming_appointments_count' => fn ($query) => $query
                    ->where('appointment_at', '>=', now())
                    ->whereIn('status', ['Pendiente', 'Confirmada']),
                'dietPlans as active_diet_plans_count' => fn ($query) => $query->where('active', true),
            ])
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$this->clinic->id])
            ->orderBy('name')
            ->get();
    }

    /** @return array<string, int|float> */
    public function totals(): array
    {
        $patients = Patient::whereIn('user_id', $this->ids);

        return [
            'members' => count($this->ids) - 1,
            'patients' => (clone $patients)->count(),
            'active_patients' => (clone $patients)->where('active', true)->count(),
            'new_patients_month' => (clone $patients)->where('created_at', '>=', now()->startOfMonth())->count(),
            'today_appointments' => Appointment::whereIn('user_id', $this->ids)->whereDate('appointment_at', today())->count(),
            'upcoming_appointments' => Appointment::whereIn('user_id', $this->ids)
                ->where('appointment_at', '>=', now())
                ->whereIn('status', ['Pendiente', 'Confirmada'])
                ->count(),
            'consultations_month' => Consultation::whereIn('user_id', $this->ids)
                ->where('consultation_at', '>=', now()->startOfMonth())
                ->count(),
            'income_month' => (float) Payment::whereIn('user_id', $this->ids)
                ->where('status', Payment::STATUS_PAID)
                ->where('paid_at', '>=', now()->startOfMonth())
                ->sum('amount'),
            'pending_payments' => Payment::whereIn('user_id', $this->ids)->where('status', Payment::STATUS_PENDING)->count(),
        ];
    }

    /**
     * Citas del mes en curso por estado (appointments.status).
     *
     * @return array<string, int>
     */
    public function appointmentStatusesThisMonth(): array
    {
        $counts = Appointment::whereIn('user_id', $this->ids)
            ->whereBetween('appointment_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(AppointmentController::STATUSES)
            ->mapWithKeys(fn (string $status) => [$status => (int) ($counts[$status] ?? 0)])
            ->all();
    }

    public function todayAppointments(): Collection
    {
        return Appointment::whereIn('user_id', $this->ids)
            ->whereDate('appointment_at', today())
            ->with(['patient', 'user'])
            ->orderBy('appointment_at')
            ->get();
    }

    public function upcomingAppointments(int $limit = 8): Collection
    {
        return Appointment::whereIn('user_id', $this->ids)
            ->where('appointment_at', '>', now()->endOfDay())
            ->whereIn('status', ['Pendiente', 'Confirmada'])
            ->with(['patient', 'user'])
            ->orderBy('appointment_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Actividad reciente de la clínica: consultas registradas y pacientes
     * nuevos, mezclados por fecha.
     */
    public function recentActivity(int $limit = 8): Collection
    {
        $consultations = Consultation::whereIn('user_id', $this->ids)
            ->with(['patient', 'user'])
            ->latest('consultation_at')
            ->limit($limit)
            ->get()
            ->map(fn (Consultation $consultation) => [
                'at' => $consultation->consultation_at,
                'icon' => 'fa-notes-medical',
                'text' => 'Consulta de '.trim(($consultation->patient?->first_name ?? '').' '.($consultation->patient?->last_name ?? '')),
                'who' => $consultation->user?->name,
            ]);

        $patients = Patient::whereIn('user_id', $this->ids)
            ->with('user')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Patient $patient) => [
                'at' => $patient->created_at,
                'icon' => 'fa-user-plus',
                'text' => 'Nuevo paciente: '.$patient->first_name.' '.$patient->last_name,
                'who' => $patient->user?->name,
            ]);

        return $consultations->concat($patients)
            ->filter(fn (array $event) => $event['at'] !== null)
            ->sortByDesc('at')
            ->take($limit)
            ->values();
    }
}
