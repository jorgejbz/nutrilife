<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\DietPlan;
use App\Models\Evaluation;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Reportes avanzados (planes Pro, Consultorio y prueba gratuita).
 *
 * A diferencia del dashboard (últimos 6 o 12 meses fijos), aquí el
 * nutriólogo elige cualquier rango de fechas y cada indicador se compara
 * con el periodo anterior de la misma duración. Todo se limita al
 * nutriólogo autenticado (user_id); evaluaciones se filtran por los
 * pacientes del nutriólogo (no tienen user_id).
 *
 * Las agrupaciones se hacen en PHP para que funcionen igual en MySQL y en
 * el SQLite de las pruebas.
 */
class AdvancedReport
{
    /** Atajos de periodo: clave de la URL => etiqueta. */
    public const PRESETS = [
        'mes' => 'Este mes',
        '3m' => 'Últimos 3 meses',
        '6m' => 'Últimos 6 meses',
        '12m' => 'Últimos 12 meses',
        'anio' => 'Este año',
    ];

    public const DEFAULT_PRESET = '6m';

    /** Rango máximo permitido (días) para no hacer reportes enormes. */
    public const MAX_DAYS = 731;

    public const WEEKDAYS = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

    public const MONTHS = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    public function __construct(
        private User $user,
        private Carbon $from,
        private Carbon $to,
        private ?string $preset = null,
    ) {
        $this->from = $from->copy()->startOfDay();
        $this->to = $to->copy()->endOfDay();
    }

    /**
     * Construye el reporte a partir de la petición (?periodo= o ?desde=&hasta=).
     * Fechas inválidas, invertidas o un rango mayor a MAX_DAYS caen al
     * periodo por defecto.
     */
    public static function fromRequest(User $user, Request $request): self
    {
        $today = now();
        $desde = self::parseDate($request->query('desde'));
        $hasta = self::parseDate($request->query('hasta'));

        if ($desde && $hasta && $desde->lte($hasta) && $desde->diffInDays($hasta) <= self::MAX_DAYS) {
            return new self($user, $desde, $hasta, null);
        }

        $preset = array_key_exists((string) $request->query('periodo'), self::PRESETS)
            ? (string) $request->query('periodo')
            : self::DEFAULT_PRESET;

        $from = match ($preset) {
            'mes' => $today->copy()->startOfMonth(),
            '3m' => $today->copy()->subMonthsNoOverflow(2)->startOfMonth(),
            '12m' => $today->copy()->subMonthsNoOverflow(11)->startOfMonth(),
            'anio' => $today->copy()->startOfYear(),
            default => $today->copy()->subMonthsNoOverflow(5)->startOfMonth(),
        };

        return new self($user, $from, $today, $preset);
    }

    private static function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }

        return $date && $date->format('Y-m-d') === $value ? $date : null;
    }

    public function from(): Carbon
    {
        return $this->from;
    }

    public function to(): Carbon
    {
        return $this->to;
    }

    public function preset(): ?string
    {
        return $this->preset;
    }

    /** "1 de mayo de 2026 al 13 de noviembre de 2026" */
    public function periodLabel(): string
    {
        return self::longDate($this->from).' al '.self::longDate($this->to);
    }

    public static function longDate(Carbon $date): string
    {
        $months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

        return $date->day.' de '.$months[$date->month - 1].' de '.$date->year;
    }

    /** Parámetros de URL que reproducen este mismo periodo. */
    public function query(): array
    {
        return $this->preset
            ? ['periodo' => $this->preset]
            : ['desde' => $this->from->toDateString(), 'hasta' => $this->to->toDateString()];
    }

    /** Periodo anterior de la misma duración, justo antes de $from. */
    private function previousRange(): array
    {
        $days = $this->from->copy()->startOfDay()->diffInDays($this->to->copy()->startOfDay()) + 1;
        $prevTo = $this->from->copy()->subDay()->endOfDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();

        return [$prevFrom, $prevTo];
    }

    /* ------------------------------------------------------------------
     | Reporte completo
     * ------------------------------------------------------------------ */

    public function all(): array
    {
        [$prevFrom, $prevTo] = $this->previousRange();

        $current = $this->summary($this->from, $this->to);
        $previous = $this->summary($prevFrom, $prevTo);

        $kpis = [];
        foreach ($current as $key => $value) {
            $kpis[$key] = [
                'value' => $value,
                'previous' => $previous[$key],
                'change' => $this->change($value, $previous[$key]),
            ];
        }

        return [
            'period' => $this->periodLabel(),
            'previous_period' => self::longDate($prevFrom).' al '.self::longDate($prevTo),
            'kpis' => $kpis,
            'income_by_method' => $this->incomeByMethod(),
            'income_by_concept' => $this->incomeByConcept(),
            'appointments_by_status' => $this->appointmentsByStatus(),
            'appointments_by_weekday' => $this->appointmentsByWeekday(),
            'top_patients' => $this->topPatientsByIncome(),
            'clinical' => $this->clinicalOutcomes(),
            'diet_plans' => $this->dietPlans(),
            'monthly' => $this->monthly(),
        ];
    }

    /** % de cambio contra el periodo anterior (null si antes era 0). */
    private function change(float|int|null $current, float|int|null $previous): ?float
    {
        if ($current === null || $previous === null || (float) $previous == 0.0) {
            return null;
        }

        return round(($current - $previous) / abs($previous) * 100, 1);
    }

    /* ------------------------------------------------------------------
     | Indicadores principales
     * ------------------------------------------------------------------ */

    /**
     * @return array{new_patients:int, patients_attended:int, returning_patients:int,
     *   consultations:int, appointments:int, completed:int, cancelled:int,
     *   attendance_rate:?float, cancellation_rate:?float, income:float,
     *   paid_payments:int, average_ticket:?float, pending_amount:float}
     */
    private function summary(Carbon $from, Carbon $to): array
    {
        $uid = $this->user->id;

        $newPatients = Patient::where('user_id', $uid)->whereBetween('created_at', [$from, $to])->count();

        $consultations = Consultation::where('user_id', $uid)
            ->whereBetween('consultation_at', [$from, $to])
            ->get(['patient_id']);
        $attendedIds = $consultations->pluck('patient_id')->unique();

        $returning = $attendedIds->isEmpty() ? 0 : Consultation::where('user_id', $uid)
            ->where('consultation_at', '<', $from)
            ->whereIn('patient_id', $attendedIds)
            ->distinct()
            ->count('patient_id');

        $appointments = Appointment::where('user_id', $uid)
            ->whereBetween('appointment_at', [$from, $to])
            ->get(['status', 'appointment_at']);
        $completed = $appointments->where('status', 'Completada')->count();
        $cancelled = $appointments->where('status', 'Cancelada')->count();
        // Asistencia: completadas entre las citas que ya pasaron y no se cancelaron.
        $pastOpen = $appointments->filter(fn ($a) => $a->status !== 'Cancelada' && $a->appointment_at->lte(now()))->count();

        $paid = Payment::where('user_id', $uid)->where('status', Payment::STATUS_PAID)
            ->whereBetween('paid_at', [$from, $to])->get(['amount']);
        $income = (float) $paid->sum(fn ($p) => (float) $p->amount);

        $pending = (float) Payment::where('user_id', $uid)->where('status', Payment::STATUS_PENDING)
            ->whereBetween('paid_at', [$from, $to])->get(['amount'])->sum(fn ($p) => (float) $p->amount);

        return [
            'new_patients' => $newPatients,
            'patients_attended' => $attendedIds->count(),
            'returning_patients' => $returning,
            'consultations' => $consultations->count(),
            'appointments' => $appointments->count(),
            'completed' => $completed,
            'cancelled' => $cancelled,
            'attendance_rate' => $pastOpen > 0 ? round($completed / $pastOpen * 100, 1) : null,
            'cancellation_rate' => $appointments->count() > 0 ? round($cancelled / $appointments->count() * 100, 1) : null,
            'income' => round($income, 2),
            'paid_payments' => $paid->count(),
            'average_ticket' => $paid->count() > 0 ? round($income / $paid->count(), 2) : null,
            'pending_amount' => round($pending, 2),
        ];
    }

    /* ------------------------------------------------------------------
     | Desgloses del periodo
     * ------------------------------------------------------------------ */

    private function paidInRange(): Collection
    {
        return Payment::where('user_id', $this->user->id)
            ->where('status', Payment::STATUS_PAID)
            ->whereBetween('paid_at', [$this->from, $this->to])
            ->get(['patient_id', 'amount', 'payment_method', 'concept', 'paid_at']);
    }

    /** @return list<array{label:string, amount:float, count:int, share:float}> */
    private function incomeByMethod(): array
    {
        return $this->groupAmounts($this->paidInRange(), fn ($p) => $p->payment_method ?: 'Otro');
    }

    /** @return list<array{label:string, amount:float, count:int, share:float}> */
    private function incomeByConcept(): array
    {
        $rows = $this->groupAmounts($this->paidInRange(), fn ($p) => trim((string) $p->concept) !== '' ? trim((string) $p->concept) : 'Sin concepto');

        return array_slice($rows, 0, 8);
    }

    private function groupAmounts(Collection $payments, callable $key): array
    {
        $total = (float) $payments->sum(fn ($p) => (float) $p->amount);

        return $payments->groupBy($key)
            ->map(fn (Collection $rows, $label) => [
                'label' => (string) $label,
                'amount' => round((float) $rows->sum(fn ($p) => (float) $p->amount), 2),
                'count' => $rows->count(),
                'share' => $total > 0 ? round($rows->sum(fn ($p) => (float) $p->amount) / $total * 100, 1) : 0.0,
            ])
            ->sortByDesc('amount')
            ->values()
            ->all();
    }

    /** @return list<array{label:string, count:int, share:float}> */
    private function appointmentsByStatus(): array
    {
        $rows = Appointment::where('user_id', $this->user->id)
            ->whereBetween('appointment_at', [$this->from, $this->to])
            ->pluck('status');
        $total = $rows->count();

        return collect(['Completada', 'Confirmada', 'Pendiente', 'Cancelada'])
            ->map(fn ($status) => [
                'label' => $status,
                'count' => $rows->filter(fn ($s) => $s === $status)->count(),
                'share' => $total > 0 ? round($rows->filter(fn ($s) => $s === $status)->count() / $total * 100, 1) : 0.0,
            ])
            ->all();
    }

    /** Citas no canceladas por día de la semana. @return list<array{label:string, count:int}> */
    private function appointmentsByWeekday(): array
    {
        $counts = array_fill(0, 7, 0);

        Appointment::where('user_id', $this->user->id)
            ->where('status', '!=', 'Cancelada')
            ->whereBetween('appointment_at', [$this->from, $this->to])
            ->pluck('appointment_at')
            ->each(function ($at) use (&$counts) {
                $counts[Carbon::parse($at)->dayOfWeekIso - 1]++;
            });

        return collect(self::WEEKDAYS)->map(fn ($day, $i) => ['label' => $day, 'count' => $counts[$i]])->all();
    }

    /** Pacientes que más pagaron en el periodo. @return list<array{name:string, amount:float, count:int}> */
    private function topPatientsByIncome(int $limit = 5): array
    {
        $grouped = $this->paidInRange()->whereNotNull('patient_id')->groupBy('patient_id');
        $names = $this->patientNames($grouped->keys());

        return $grouped
            ->map(fn (Collection $rows, $id) => [
                'name' => $names[$id] ?? 'Paciente',
                'amount' => round((float) $rows->sum(fn ($p) => (float) $p->amount), 2),
                'count' => $rows->count(),
            ])
            ->sortByDesc('amount')
            ->take($limit)
            ->values()
            ->all();
    }

    private function patientNames(Collection $ids): array
    {
        if ($ids->isEmpty()) {
            return [];
        }

        return Patient::withTrashed()
            ->where('user_id', $this->user->id)
            ->whereIn('id', $ids)
            ->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn ($p) => [$p->id => trim($p->first_name.' '.$p->last_name)])
            ->all();
    }

    /**
     * Resultados clínicos: pacientes con 2 o más evaluaciones dentro del
     * periodo; se compara la primera con la última.
     */
    private function clinicalOutcomes(): array
    {
        $patientIds = Patient::where('user_id', $this->user->id)->pluck('id');

        $evaluations = $patientIds->isEmpty() ? collect() : Evaluation::whereIn('patient_id', $patientIds)
            ->whereBetween('evaluation_date', [$this->from->toDateString(), $this->to->toDateString()])
            ->orderBy('evaluation_date')->orderBy('id')
            ->get(['patient_id', 'evaluation_date', 'weight', 'bmi', 'body_fat', 'waist']);

        $groups = $evaluations->groupBy('patient_id')->filter(fn ($rows) => $rows->count() >= 2);
        $names = $this->patientNames($groups->keys());

        $diff = fn ($a, $b) => ($a !== null && $b !== null) ? round($b - $a, 1) : null;

        $rows = $groups->map(function (Collection $evals, $id) use ($names, $diff) {
            $first = $evals->first();
            $last = $evals->last();

            return [
                'name' => $names[$id] ?? 'Paciente',
                'evaluations' => $evals->count(),
                'weight_start' => $first->weight,
                'weight_end' => $last->weight,
                'weight_change' => $diff($first->weight, $last->weight),
                'bmi_change' => $diff($first->bmi, $last->bmi),
                'fat_change' => $diff($first->body_fat, $last->body_fat),
                'waist_change' => $diff($first->waist, $last->waist),
            ];
        })->sortBy(fn ($r) => $r['weight_change'] ?? INF)->values();

        $changes = $rows->pluck('weight_change')->filter(fn ($v) => $v !== null);
        $stable = DashboardStats::WEIGHT_STABLE_KG;

        return [
            'evaluations' => $evaluations->count(),
            'patients' => $rows->count(),
            'avg_weight_change' => $changes->isNotEmpty() ? round($changes->avg(), 1) : null,
            'total_kg_lost' => round(abs($changes->filter(fn ($v) => $v < 0)->sum()), 1),
            'lost' => $changes->filter(fn ($v) => $v <= -$stable)->count(),
            'maintained' => $changes->filter(fn ($v) => abs($v) < $stable)->count(),
            'gained' => $changes->filter(fn ($v) => $v >= $stable)->count(),
            'rows' => $rows->all(),
        ];
    }

    private function dietPlans(): array
    {
        $created = DietPlan::ownedBy($this->user)
            ->whereBetween('created_at', [$this->from, $this->to])
            ->get(['target_kcal', 'patient_id']);

        $kcal = $created->pluck('target_kcal')->map(fn ($v) => (float) $v)->filter(fn ($v) => $v > 0);

        return [
            'created' => $created->count(),
            'patients' => $created->pluck('patient_id')->unique()->count(),
            'active_now' => DietPlan::ownedBy($this->user)->where('active', true)->count(),
            'avg_target_kcal' => $kcal->isNotEmpty() ? round($kcal->avg()) : null,
        ];
    }

    /** Serie mensual del periodo: pacientes nuevos, consultas e ingresos. */
    private function monthly(): array
    {
        $uid = $this->user->id;
        $months = [];
        $cursor = $this->from->copy()->startOfMonth();

        while ($cursor->lte($this->to) && count($months) < 25) {
            $key = $cursor->format('Y-m');
            $months[$key] = [
                'label' => self::MONTHS[$cursor->month - 1].' '.$cursor->format('y'),
                'new_patients' => 0,
                'consultations' => 0,
                'income' => 0.0,
            ];
            $cursor->addMonthNoOverflow();
        }

        $range = [$this->from, $this->to];

        Patient::where('user_id', $uid)->whereBetween('created_at', $range)->pluck('created_at')
            ->each(function ($d) use (&$months) {
                $k = Carbon::parse($d)->format('Y-m');
                isset($months[$k]) && $months[$k]['new_patients']++;
            });

        Consultation::where('user_id', $uid)->whereBetween('consultation_at', $range)->pluck('consultation_at')
            ->each(function ($d) use (&$months) {
                $k = Carbon::parse($d)->format('Y-m');
                isset($months[$k]) && $months[$k]['consultations']++;
            });

        $this->paidInRange()->each(function ($p) use (&$months) {
            $k = Carbon::parse($p->paid_at)->format('Y-m');
            if (isset($months[$k])) {
                $months[$k]['income'] += (float) $p->amount;
            }
        });

        return array_values($months);
    }

    /* ------------------------------------------------------------------
     | CSV
     * ------------------------------------------------------------------ */

    /** Filas para el CSV (secciones separadas por una fila vacía). */
    public function csvRows(array $data): array
    {
        $labels = self::kpiLabels();
        $rows = [['Reporte avanzado NutriLife'], ['Periodo', $data['period']], ['Periodo anterior', $data['previous_period']], []];

        $rows[] = ['Indicador', 'Periodo', 'Periodo anterior', 'Cambio %'];
        foreach ($data['kpis'] as $key => $kpi) {
            $rows[] = [$labels[$key] ?? $key, $kpi['value'] ?? '', $kpi['previous'] ?? '', $kpi['change'] ?? ''];
        }

        $rows[] = [];
        $rows[] = ['Mes', 'Pacientes nuevos', 'Consultas', 'Ingresos (MXN)'];
        foreach ($data['monthly'] as $m) {
            $rows[] = [$m['label'], $m['new_patients'], $m['consultations'], round($m['income'], 2)];
        }

        $rows[] = [];
        $rows[] = ['Método de pago', 'Ingresos (MXN)', 'Pagos', '% del total'];
        foreach ($data['income_by_method'] as $r) {
            $rows[] = [$r['label'], $r['amount'], $r['count'], $r['share']];
        }

        $rows[] = [];
        $rows[] = ['Concepto', 'Ingresos (MXN)', 'Pagos', '% del total'];
        foreach ($data['income_by_concept'] as $r) {
            $rows[] = [$r['label'], $r['amount'], $r['count'], $r['share']];
        }

        $rows[] = [];
        $rows[] = ['Estado de cita', 'Citas', '% del total'];
        foreach ($data['appointments_by_status'] as $r) {
            $rows[] = [$r['label'], $r['count'], $r['share']];
        }

        $rows[] = [];
        $rows[] = ['Paciente', 'Evaluaciones', 'Peso inicial (kg)', 'Peso final (kg)', 'Cambio peso (kg)', 'Cambio IMC', 'Cambio grasa (%)', 'Cambio cintura (cm)'];
        foreach ($data['clinical']['rows'] as $r) {
            $rows[] = [$r['name'], $r['evaluations'], $r['weight_start'], $r['weight_end'], $r['weight_change'], $r['bmi_change'], $r['fat_change'], $r['waist_change']];
        }

        return $rows;
    }

    public static function kpiLabels(): array
    {
        return [
            'new_patients' => 'Pacientes nuevos',
            'patients_attended' => 'Pacientes atendidos',
            'returning_patients' => 'Pacientes que regresaron',
            'consultations' => 'Consultas',
            'appointments' => 'Citas agendadas',
            'completed' => 'Citas completadas',
            'cancelled' => 'Citas canceladas',
            'attendance_rate' => 'Asistencia (%)',
            'cancellation_rate' => 'Cancelación (%)',
            'income' => 'Ingresos cobrados (MXN)',
            'paid_payments' => 'Pagos cobrados',
            'average_ticket' => 'Ticket promedio (MXN)',
            'pending_amount' => 'Pagos pendientes (MXN)',
        ];
    }
}
