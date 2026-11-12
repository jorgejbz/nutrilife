<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AppointmentAvailability;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Citas del paciente: ver, horas libres, cancelar y reprogramar. */
class AppointmentController extends Controller
{
    public function __construct(private AppointmentAvailability $availability) {}

    // GET /api/appointments -> pasadas y futuras, con la bandera puede_modificar
    public function index(Request $request): JsonResponse
    {
        $rows = DB::table('appointments')
            ->where('patient_id', $request->user()->id)
            ->orderByDesc('appointment_at')
            ->get(['id', 'appointment_at', 'reason', 'status'])
            ->map(fn ($a) => $this->present($a));

        return response()->json($rows);
    }

    // GET /api/availability?from=Y-m-d&to=Y-m-d -> días y horas libres del nutriólogo
    public function availability(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => 'required|date_format:Y-m-d',
            'to' => 'required|date_format:Y-m-d|after_or_equal:from',
        ]);

        $from = Carbon::parse($data['from']);
        $to = Carbon::parse($data['to']);

        if ($from->diffInDays($to) > 31) {
            return response()->json(['message' => 'El rango máximo es de 31 días'], 422);
        }

        return response()->json([
            'desde' => $from->toDateString(),
            'hasta' => $to->toDateString(),
            'dias' => $this->availability->freeSlots($request->user()->user_id, $from, $to),
        ]);
    }

    // POST /api/appointments/{id}/cancel  { reason?: string }
    public function cancel(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['reason' => 'nullable|string|max:255']);

        $appt = $this->findOwn($request, $id);

        if (! $this->availability->canModify($appt->status, $appt->appointment_at)) {
            return $this->notModifiable($appt->status);
        }

        $reason = trim((string) ($data['reason'] ?? ''));
        $note = 'Cancelada por el paciente desde la app ('.now()->format('Y-m-d H:i').')'
            .($reason !== '' ? ": $reason" : '');

        DB::table('appointments')->where('id', $id)->update([
            'status' => 'Cancelada',
            'notes' => trim(($appt->notes ? $appt->notes."\n" : '').$note),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Tu cita fue cancelada',
            'cita' => $this->present($this->findOwn($request, $id)),
        ]);
    }

    // POST /api/appointments/{id}/reschedule  { appointment_at: "Y-m-d H:i" }
    public function reschedule(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['appointment_at' => 'required|date_format:Y-m-d H:i']);

        $appt = $this->findOwn($request, $id);

        if (! $this->availability->canModify($appt->status, $appt->appointment_at)) {
            return $this->notModifiable($appt->status);
        }

        $new = Carbon::createFromFormat('Y-m-d H:i', $data['appointment_at']);

        return DB::transaction(function () use ($request, $appt, $new, $id) {
            // Bloquea las citas del nutriólogo ese día para que dos pacientes no tomen la misma hora
            DB::table('appointments')
                ->where('user_id', $appt->user_id)
                ->whereDate('appointment_at', $new->toDateString())
                ->lockForUpdate()
                ->get();

            $day = collect($this->availability->freeSlots($appt->user_id, $new, $new, $appt->id))
                ->firstWhere('fecha', $new->toDateString());

            if (! $day || ! in_array($new->format('H:i'), $day['horas'], true)) {
                return response()->json(['message' => 'Ese horario ya no está disponible'], 409);
            }

            $note = 'Reprogramada por el paciente desde la app ('.now()->format('Y-m-d H:i')
                .'). Antes: '.Carbon::parse($appt->appointment_at)->format('Y-m-d H:i');

            DB::table('appointments')->where('id', $id)->update([
                'appointment_at' => $new->format('Y-m-d H:i:00'),
                'status' => config('appointments.status_after_reschedule'),
                'notes' => trim(($appt->notes ? $appt->notes."\n" : '').$note),
                'updated_at' => now(),
            ]);

            return response()->json([
                'message' => 'Tu cita fue reprogramada',
                'cita' => $this->present($this->findOwn($request, $id)),
            ]);
        });
    }

    /** Solo citas del paciente que inició sesión. */
    private function findOwn(Request $request, int $id): object
    {
        $appt = DB::table('appointments')
            ->where('id', $id)
            ->where('patient_id', $request->user()->id)
            ->first();

        abort_if(! $appt, 404, 'Cita no encontrada');

        return $appt;
    }

    private function present(object $a): array
    {
        return [
            'id' => (int) $a->id,
            'appointment_at' => $a->appointment_at,
            'reason' => $a->reason,
            'status' => $a->status,
            'puede_modificar' => $this->availability->canModify($a->status, $a->appointment_at),
        ];
    }

    private function notModifiable(?string $status): JsonResponse
    {
        if (! in_array($status, config('appointments.modifiable_statuses'), true)) {
            return response()->json([
                'message' => 'Esta cita ya no se puede modificar (estatus: '.($status ?? 'sin estatus').')',
            ], 422);
        }

        $h = config('appointments.min_hours_before');

        return response()->json([
            'message' => "Solo puedes cambiar o cancelar tu cita con al menos $h horas de anticipación",
        ], 422);
    }
}
