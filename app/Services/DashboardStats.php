<?php

namespace App\Services;

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PaymentController;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\DietPlan;
use App\Models\Evaluation;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
 *  - Perfil clínico: evaluations y patient_pathologies no tienen user_id,
 *    así que se filtran por patients.user_id (sin pacientes eliminados).
 *
 * Las series del periodo (?meses=6|12) agrupan en PHP las filas del rango
 * (igual en MySQL y en el SQLite de las pruebas).
 *
 * Fechas en config('app.timezone'). Las gráficas mensuales usan los
 * últimos N meses incluyendo el actual.
 */
class DashboardStats
{
    public const PERIODS = [6, 12];

    /** Rangos de edad (años cumplidos, límite superior incluido; null = sin tope). */
    public const AGE_RANGES = ['0-17' => [0, 17], '18-29' => [18, 29], '30-44' => [30, 44], '45-59' => [45, 59], '60+' => [60, null]];

    public const SEXES = ['Femenino', 'Masculino', 'Otro'];

    /** Clasificación de IMC de la OMS: [etiqueta, mínimo incluido]. */
    public const BMI_CATEGORIES = [
        ['Bajo peso', 0],
        ['Normal', 18.5],
        ['Sobrepeso', 25],
        ['Obesidad I', 30],
        ['Obesidad II', 35],
        ['Obesidad III', 40],
    ];

    /** Diferencia de peso (kg) que todavía cuenta como «mantuvo». */
    public const WEIGHT_STABLE_KG = 0.5;

    public const TOP_PATHOLOGIES = 8;

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
        $appointmentStatus = $this->appointmentStatusSeries($monthsRange);
        $weight = $this->weightEvolution();

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

            // Tasa de cancelación del periodo (null si no hubo citas)
            'cancellationRate' => $appointmentStatus['cancellation_rate'],
            'weightEvolution' => $weight,
            'demand' => $this->demandHeatmap($monthsRange),

            'charts' => [
                'patients' => $this->patientsSeries($monthsRange),
                'appointments' => $this->weekSeries(),
                'income' => $this->incomeSeries($monthsRange),
                'status' => ['active' => $activePatients, 'inactive' => $totalPatients - $activePatients],
                'paymentMethods' => $this->paymentMethodsSeries($monthsRange),
                'appointmentStatus' => $appointmentStatus,
                'consultations' => $this->consultationsSeries($monthsRange),
                'demographics' => $this->demographics(),
                'pathologies' => $this->topPathologies(),
                'bmi' => $this->bmiCategories(),
                'weight' => [
                    'labels' => ['Bajó', 'Mantuvo', 'Subió'],
                    'data' => [$weight['lost'], $weight['kept'], $weight['gained']],
                ],
            ],
        ];
    }

    /** Inicio del primer mes y fin del mes actual del periodo. */
    private function period(array $months): array
    {
        return [$months[0]->copy(), end($months)->copy()->endOfMonth()];
    }

    /** Llave «Y-m» de una fecha (para agrupar por mes en PHP). */
    private function monthKey($date): string
    {
        return Carbon::parse($date)->format('Y-m');
    }

    /** Pacientes vigentes del nutriólogo (Eloquent excluye los eliminados). */
    private function patientIds(): Collection
    {
        return Patient::where('user_id', $this->user->id)->pluck('id');
    }

    /** Ingresos con estado Pagado por método de pago en el periodo (orden fijo). */
    private function paymentMethodsSeries(array $months): array
    {
        $totals = Payment::where('user_id', $this->user->id)
            ->where('status', Payment::STATUS_PAID)
            ->whereBetween('paid_at', $this->period($months))
            ->get(['payment_method', 'amount'])
            ->groupBy('payment_method')
            ->map(fn (Collection $rows) => round((float) $rows->sum('amount'), 2));

        return [
            'labels' => PaymentController::METHODS,
            'data' => array_map(fn ($method) => (float) ($totals[$method] ?? 0), PaymentController::METHODS),
        ];
    }

    /**
     * Citas por estado y mes (todas, incluidas las canceladas) y la tasa de
     * cancelación del periodo.
     */
    private function appointmentStatusSeries(array $months): array
    {
        $rows = Appointment::where('user_id', $this->user->id)
            ->whereBetween('appointment_at', $this->period($months))
            ->get(['status', 'appointment_at']);

        $byMonth = $rows->groupBy(fn ($a) => $a->status.'|'.$this->monthKey($a->appointment_at))->map->count();
        $series = [];

        foreach (AppointmentController::STATUSES as $status) {
            $series[$status] = array_map(fn ($m) => (int) ($byMonth[$status.'|'.$m->format('Y-m')] ?? 0), $months);
        }

        $total = $rows->count();
        $cancelled = $rows->where('status', 'Cancelada')->count();

        return [
            'labels' => array_map(fn ($m) => $this->label($m), $months),
            'series' => $series,
            'cancellation_rate' => $total > 0 ? (int) round($cancelled / $total * 100) : null,
        ];
    }

    /** Consultas registradas por mes. */
    private function consultationsSeries(array $months): array
    {
        $counts = Consultation::where('user_id', $this->user->id)
            ->whereBetween('consultation_at', $this->period($months))
            ->get(['consultation_at'])
            ->countBy(fn ($c) => $this->monthKey($c->consultation_at));

        return [
            'labels' => array_map(fn ($m) => $this->label($m), $months),
            'data' => array_map(fn ($m) => (int) ($counts[$m->format('Y-m')] ?? 0), $months),
        ];
    }

    /**
     * Pacientes por sexo y rango de edad (edad desde birth_date). Los que no
     * tienen fecha de nacimiento o sexo se cuentan aparte: no se adivinan.
     */
    private function demographics(): array
    {
        $patients = Patient::where('user_id', $this->user->id)->get(['sex', 'birth_date']);
        $series = array_fill_keys(self::SEXES, array_fill_keys(array_keys(self::AGE_RANGES), 0));
        $missing = 0;

        foreach ($patients as $patient) {
            $range = $patient->birth_date ? $this->ageRange(Carbon::parse($patient->birth_date)->age) : null;

            if (! $range || ! in_array($patient->sex, self::SEXES, true)) {
                $missing++;

                continue;
            }

            $series[$patient->sex][$range]++;
        }

        return [
            'labels' => array_keys(self::AGE_RANGES),
            'series' => array_map('array_values', $series),
            'missing' => $missing,
        ];
    }

    private function ageRange(int $age): ?string
    {
        foreach (self::AGE_RANGES as $label => [$min, $max]) {
            if ($age >= $min && ($max === null || $age <= $max)) {
                return $label;
            }
        }

        return null;
    }

    /** Patologías con más pacientes (vigentes) del nutriólogo. */
    private function topPathologies(): array
    {
        $rows = DB::table('patient_pathologies')
            ->join('patients', 'patients.id', '=', 'patient_pathologies.patient_id')
            ->join('pathologies', 'pathologies.id', '=', 'patient_pathologies.pathology_id')
            ->where('patients.user_id', $this->user->id)
            ->whereNull('patients.deleted_at')
            ->groupBy('pathologies.id', 'pathologies.name')
            ->orderByDesc('total')
            ->orderBy('pathologies.name')
            ->limit(self::TOP_PATHOLOGIES)
            ->get(['pathologies.name', DB::raw('COUNT(DISTINCT patient_pathologies.patient_id) as total')]);

        return [
            'labels' => $rows->pluck('name')->all(),
            'data' => $rows->pluck('total')->map(fn ($total) => (int) $total)->all(),
        ];
    }

    /** IMC de la última evaluación (con IMC) de cada paciente, en categorías de la OMS. */
    private function bmiCategories(): array
    {
        $latest = Evaluation::whereIn('patient_id', $this->patientIds())
            ->whereNotNull('bmi')
            ->orderByDesc('evaluation_date')
            ->orderByDesc('id')
            ->get(['patient_id', 'bmi'])
            ->unique('patient_id');

        $counts = array_fill(0, count(self::BMI_CATEGORIES), 0);

        foreach ($latest as $evaluation) {
            $bmi = (float) $evaluation->bmi;
            $index = 0;

            foreach (self::BMI_CATEGORIES as $i => [, $min]) {
                if ($bmi >= $min) {
                    $index = $i;
                }
            }

            $counts[$index]++;
        }

        return [
            'labels' => array_column(self::BMI_CATEGORIES, 0),
            'data' => $counts,
        ];
    }

    /**
     * Peso: primera vs. última evaluación con peso de cada paciente que
     * tenga al menos dos. average_change < 0 = kg perdidos en promedio.
     */
    private function weightEvolution(): array
    {
        $byPatient = Evaluation::whereIn('patient_id', $this->patientIds())
            ->whereNotNull('weight')
            ->orderBy('evaluation_date')
            ->orderBy('id')
            ->get(['patient_id', 'weight'])
            ->groupBy('patient_id')
            ->filter(fn (Collection $rows) => $rows->count() >= 2)
            ->map(fn (Collection $rows) => (float) $rows->last()->weight - (float) $rows->first()->weight);

        $total = $byPatient->count();
        $lost = $byPatient->filter(fn ($diff) => $diff <= -self::WEIGHT_STABLE_KG)->count();
        $gained = $byPatient->filter(fn ($diff) => $diff >= self::WEIGHT_STABLE_KG)->count();
        $pct = fn (int $count) => $total > 0 ? (int) round($count / $total * 100) : 0;

        return [
            'patients' => $total,
            'lost' => $lost,
            'kept' => $total - $lost - $gained,
            'gained' => $gained,
            'lost_pct' => $pct($lost),
            'kept_pct' => $pct($total - $lost - $gained),
            'gained_pct' => $pct($gained),
            'average_change' => $total > 0 ? round($byPatient->avg(), 1) : null,
        ];
    }

    /**
     * Citas no canceladas del periodo por día de la semana (lunes a domingo)
     * y hora. Las horas mostradas van de la primera a la última con citas.
     */
    private function demandHeatmap(array $months): array
    {
        $rows = Appointment::where('user_id', $this->user->id)
            ->where('status', '!=', 'Cancelada')
            ->whereBetween('appointment_at', $this->period($months))
            ->get(['appointment_at'])
            ->map(fn ($a) => Carbon::parse($a->appointment_at));

        $days = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

        if ($rows->isEmpty()) {
            return ['days' => $days, 'hours' => [], 'grid' => [], 'max' => 0, 'total' => 0];
        }

        $hours = range($rows->min(fn ($d) => $d->hour), $rows->max(fn ($d) => $d->hour));
        $counts = $rows->countBy(fn ($d) => ($d->dayOfWeekIso - 1).'-'.$d->hour);

        $grid = array_map(
            fn ($day) => array_map(fn ($hour) => (int) ($counts[$day.'-'.$hour] ?? 0), $hours),
            array_keys($days)
        );

        return [
            'days' => $days,
            'hours' => $hours,
            'grid' => $grid,
            'max' => max(array_map('max', $grid)),
            'total' => $rows->count(),
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

    /**
     * Ingresos por mes de paid_at: cobrados (status Pagado, `data`) y
     * pendientes (status Pendiente, `pending`). Los cancelados no cuentan.
     */
    private function incomeSeries(array $months): array
    {
        $totals = Payment::where('user_id', $this->user->id)
            ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_PENDING])
            ->whereBetween('paid_at', $this->period($months))
            ->get(['status', 'amount', 'paid_at'])
            ->groupBy(fn ($p) => $p->status.'|'.$this->monthKey($p->paid_at))
            ->map(fn (Collection $rows) => round((float) $rows->sum('amount'), 2));

        $series = fn (string $status) => array_map(
            fn ($m) => (float) ($totals[$status.'|'.$m->format('Y-m')] ?? 0),
            $months
        );

        return [
            'labels' => array_map(fn ($m) => $this->label($m), $months),
            'data' => $series(Payment::STATUS_PAID),
            'pending' => $series(Payment::STATUS_PENDING),
        ];
    }
}
