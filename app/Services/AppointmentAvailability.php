<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Horas libres del nutriólogo y reglas para que el paciente cancele o
 * reprograme desde la app móvil (config/appointments.php).
 */
class AppointmentAvailability
{
    /**
     * Días con horas libres entre $from y $to para un nutriólogo.
     *
     * @return list<array{fecha: string, horas: list<string>}>
     */
    public function freeSlots(int $nutriId, Carbon $from, Carbon $to, ?int $ignoreAppointmentId = null): array
    {
        $cfg = config('appointments');
        $slot = (int) $cfg['slot_minutes'];

        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();
        $earliest = now()->addHours($cfg['min_hours_before']);
        $lastDay = now()->addDays($cfg['max_days_ahead'])->endOfDay();

        // Citas activas del nutriólogo (de cualquier paciente): solo se usan para descartar horas
        $busy = DB::table('appointments')
            ->where('user_id', $nutriId)
            ->where('status', '!=', 'Cancelada')
            ->when($ignoreAppointmentId, fn ($q) => $q->where('id', '!=', $ignoreAppointmentId))
            ->whereBetween('appointment_at', [$from->copy()->subMinutes($slot), $to])
            ->pluck('appointment_at')
            ->map(fn ($d) => Carbon::parse($d))
            ->all();

        $days = [];

        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $date = $day->toDateString();

            if ($day->gt($lastDay) || in_array($date, $cfg['closed_dates'], true)) {
                continue;
            }

            $hours = [];
            foreach ($cfg['working_hours'][$day->dayOfWeekIso] ?? [] as [$start, $end]) {
                $t = Carbon::parse("$date $start");
                $limit = Carbon::parse("$date $end");

                while ($t->copy()->addMinutes($slot)->lte($limit)) {
                    if ($t->gte($earliest) && ! $this->overlaps($t, $slot, $busy)) {
                        $hours[] = $t->format('H:i');
                    }
                    $t->addMinutes($slot);
                }
            }

            if ($hours) {
                $days[] = ['fecha' => $date, 'horas' => $hours];
            }
        }

        return $days;
    }

    /** ¿El paciente todavía puede cancelar o reprogramar esta cita? */
    public function canModify(?string $status, ?string $appointmentAt): bool
    {
        return $status !== null && $appointmentAt !== null
            && in_array($status, config('appointments.modifiable_statuses'), true)
            && Carbon::parse($appointmentAt)->gte(now()->addHours(config('appointments.min_hours_before')));
    }

    private function overlaps(Carbon $start, int $slot, array $busy): bool
    {
        $end = $start->copy()->addMinutes($slot);

        foreach ($busy as $b) {
            if ($b->lt($end) && $b->copy()->addMinutes($slot)->gt($start)) {
                return true;
            }
        }

        return false;
    }
}
