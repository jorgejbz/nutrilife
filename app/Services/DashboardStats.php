<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\DietPlan;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Indicadores del dashboard, siempre del nutriólogo autenticado
 * (filtro por user_id en cada consulta).
 *
 * Fuentes:
 *  - Pacientes: patients (sin eliminados; active = estado).
 *  - Agenda: appointments (no se cuentan las Canceladas).
 *  - Consultas: consultations.consultation_at.
 *  - Ingresos: payments con status = 'Pagado' por paid_at (MXN).
 *  - Planes alimenticios: diet_plans activos (solo si el plan lo incluye).
 *
 * Fechas en config('app.timezone'). Las gráficas mensuales usan los
 * últimos N meses incluyendo el actual.
 */
class DashboardStats
{
    public const PERIODS = [6, 12];

    public function __construct(private User $user, private int $months = 6)
    {
        $this->months = in_array($months, self::PERIODS, true) ? $months : 6;
    }

    public function months(): int
    {
        return $this->months;
    }

    public function all(bool $withDietPlans = false): array
    {
        $now = now();
        $userId = $this->user->id;

        /* ---------- Pacientes ---------- */
        $patients = Patient::where('user_id', $userId);
        $totalPatients = (clone $patients)->count();
        $activePatients = (clone $patients)->where('active', true)->count();
        $newPatientsThisMonth = (clone $patients)->where('created_at', '>=', $now->copy()->startOfMonth())->count();

        /* ---------- Agenda ---------- */
        $appointments = Appointment::where('user_id', $userId)->where('status', '!=', 'Cancelada');

        $todayAppointments = (clone $appointments)->whereBetween('appointment_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()]);
        $todayCount = (clone $todayAppointments)->count();
        $todayDone = (clone $todayAppointments)->where('status', 'Completada')->count();

        $monthAppointments = (clone $appointments)
            ->whereBetween('appointment_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->count();

        $nextSevenDays = (clone $appointments)
            ->whereIn('status', ['Pendiente', 'Confirmada'])
            ->whereBetween('appointment_at', [$now, $now->copy()->addDays(7)->endOfDay()])
            ->count();

        /* ---------- Consultas ---------- */
        $monthConsultations = Consultation::where('user_id', $userId)
            ->whereBetween('consultation_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->count();

        // Citas del mes que ya pasaron (no canceladas): base para "% atendidas"
        $monthPastAppointments = (clone $appointments)
            ->whereBetween('appointment_at', [$now->copy()->startOfMonth(), $now])
            ->count();
        $monthCompleted = (clone $appointments)
            ->where('status', 'Completada')
            ->whereBetween('appointment_at', [$now->copy()->startOfMonth(), $now])
            ->count();

        /* ---------- Ingresos ---------- */
        $paid = Payment::where('user_id', $userId)->where('status', 'Pagado');
        $monthIncome = (float) (clone $paid)->whereBetween('paid_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])->sum('amount');
        $previousIncome = (float) (clone $paid)->whereBetween('paid_at', [
            $now->copy()->subMonthNoOverflow()->startOfMonth(),
            $now->copy()->subMonthNoOverflow()->endOfMonth(),
        ])->sum('amount');
        $pendingPayments = Payment::where('user_id', $userId)->where('status', 'Pendiente');
        $pendingCount = (clone $pendingPayments)->count();
        $pendingAmount = (float) (clone $pendingPayments)->sum('amount');

        /* ---------- Series ---------- */
        $monthsRange = $this->monthsRange();

        return [
            'totalPatients' => $totalPatients,
            'activePatients' => $activePatients,
            'inactivePatients' => $totalPatients - $activePatients,
            'activePercentage' => $totalPatients > 0 ? (int) round($activePatients / $totalPatients * 100) : 0,
            'newPatientsThisMonth' => $newPatientsThisMonth,

            'todayAppointmentsCount' => $todayCount,
            'todayCompletedCount' => $todayDone,
            'monthAppointments' => $monthAppointments,
            'nextSevenDays' => $nextSevenDays,

            'monthConsultations' => $monthConsultations,
            'monthAttendance' => $monthPastAppointments > 0 ? (int) round($monthCompleted / $monthPastAppointments * 100) : null,

            'monthIncome' => $monthIncome,
            'incomeGrowth' => $previousIncome > 0 ? (int) round(($monthIncome - $previousIncome) / $previousIncome * 100) : null,
            'pendingPaymentsCount' => $pendingCount,
            'pendingPaymentsAmount' => $pendingAmount,

            'activeDietPlans' => $withDietPlans
                ? DietPlan::where('user_id', $userId)->where('active', true)->count()
                : null,

            'upcomingAppointments' => Appointment::where('user_id', $userId)
                ->whereIn('status', ['Pendiente', 'Confirmada'])
                ->where('appointment_at', '>=', $now)
                ->with('patient')
                ->orderBy('appointment_at')
                ->limit(5)
                ->get(),

            'charts' => [
                'patients' => $this->patientsSeries($monthsRange),
                'appointments' => $this->weekSeries(),
                'income' => $this->incomeSeries($monthsRange),
                'status' => ['active' => $activePatients, 'inactive' => $totalPatients - $activePatients],
            ],
        ];
    }

    /** @return list<Carbon> primer día de cada mes, del más antiguo al actual */
    private function monthsRange(): array
    {
        $start = now()->startOfMonth()->subMonthsNoOverflow($this->months - 1);

        return array_map(fn ($i) => $start->copy()->addMonthsNoOverflow($i), range(0, $this->months - 1));
    }

    private function label(Carbon $month): string
    {
        $label = ucfirst(rtrim($month->locale('es')->translatedFormat('M'), '.'));

        return $this->months > 6 || $month->year !== now()->year ? $label.' '.$month->format('y') : $label;
    }

    /**
     * Pacientes registrados acumulados al cierre de cada mes (sin los
     * eliminados) y altas de cada mes.
     */
    private function patientsSeries(array $months): array
    {
        $base = Patient::where('user_id', $this->user->id);

        return [
            'labels' => array_map(fn ($m) => $this->label($m), $months),
            'total' => array_map(fn ($m) => (clone $base)->where('created_at', '<=', $m->copy()->endOfMonth())->count(), $months),
            'new' => array_map(fn ($m) => (clone $base)->whereBetween('created_at', [$m, $m->copy()->endOfMonth()])->count(), $months),
        ];
    }

    /** Citas no canceladas de lunes a domingo de la semana actual. */
    private function weekSeries(): array
    {
        $monday = now()->startOfWeek(Carbon::MONDAY);
        $days = array_map(fn ($i) => $monday->copy()->addDays($i), range(0, 6));

        $counts = Appointment::where('user_id', $this->user->id)
            ->where('status', '!=', 'Cancelada')
            ->whereBetween('appointment_at', [$monday, $monday->copy()->addDays(6)->endOfDay()])
            ->get(['appointment_at'])
            ->countBy(fn ($a) => Carbon::parse($a->appointment_at)->toDateString());

        return [
            'labels' => array_map(fn ($d) => ucfirst(rtrim($d->locale('es')->translatedFormat('D'), '.')).' '.$d->format('j'), $days),
            'data' => array_map(fn ($d) => (int) ($counts[$d->toDateString()] ?? 0), $days),
            'today' => (int) now()->dayOfWeekIso - 1,
            'range' => $monday->format('d/m').' – '.$monday->copy()->addDays(6)->format('d/m'),
        ];
    }

    /** Ingresos cobrados (status Pagado) por mes. */
    private function incomeSeries(array $months): array
    {
        $base = Payment::where('user_id', $this->user->id)->where('status', 'Pagado');

        return [
            'labels' => array_map(fn ($m) => $this->label($m), $months),
            'data' => array_map(fn ($m) => round((float) (clone $base)->whereBetween('paid_at', [$m, $m->copy()->endOfMonth()])->sum('amount'), 2), $months),
        ];
    }
}
