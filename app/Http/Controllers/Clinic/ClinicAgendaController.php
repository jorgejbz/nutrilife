<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Agenda de todos los profesionales del consultorio (/clinica/agenda),
 * solo lectura. Filtros: nutriólogo, paciente, rango de fechas y estado,
 * más los accesos rápidos ?dia=ayer|hoy|manana (un solo día; reemplaza
 * desde/hasta y conserva los demás filtros). Sin filtros: de hoy en
 * adelante. Cada nutriólogo sigue creando y cancelando sus citas desde /citas.
 */
class ClinicAgendaController extends Controller
{
    /** ?dia= → días de diferencia con hoy (zona horaria de config/app.php). */
    public const QUICK_DAYS = ['ayer' => -1, 'hoy' => 0, 'manana' => 1];

    public function index(Request $request)
    {
        $clinic = $request->user();
        $ids = $clinic->clinicNutritionistIds();
        $nutritionists = User::whereIn('id', $ids)->orderBy('name')->get(['id', 'name', 'clinic_id']);

        $nutritionistId = in_array($request->integer('nutriologo'), $ids, true) ? $request->integer('nutriologo') : null;
        $status = in_array($request->query('estado'), AppointmentController::STATUSES, true) ? $request->query('estado') : null;
        $search = trim((string) $request->query('paciente'));
        $quickDay = array_key_exists($request->query('dia'), self::QUICK_DAYS) ? $request->query('dia') : null;

        if ($quickDay) {
            $from = today()->addDays(self::QUICK_DAYS[$quickDay]);
            $to = $from->copy();
        } else {
            $from = $this->date($request->query('desde')) ?? today();
            $to = $this->date($request->query('hasta'));

            if ($to && $to->lt($from)) {
                [$from, $to] = [$to, $from];
            }
        }

        // Un rango manual de un solo día que coincide con ayer/hoy/mañana
        // también marca su acceso rápido.
        $activeDay = $quickDay ?? ($to && $from->equalTo($to)
            ? array_search((int) today()->diffInDays($from, false), self::QUICK_DAYS, true) ?: null
            : null);

        $appointments = Appointment::whereIn('user_id', $nutritionistId ? [$nutritionistId] : $ids)
            ->with(['patient', 'user:id,name,clinic_id'])
            ->where('appointment_at', '>=', $from->copy()->startOfDay())
            ->when($to, fn ($query) => $query->where('appointment_at', '<=', $to->copy()->endOfDay()))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('patient', function ($patient) use ($search) {
                    foreach (preg_split('/\s+/', $search) as $word) {
                        $patient->where(fn ($q) => $q
                            ->where('first_name', 'like', '%'.$word.'%')
                            ->orWhere('last_name', 'like', '%'.$word.'%'));
                    }
                });
            })
            ->orderBy('appointment_at')
            ->paginate(25)
            ->withQueryString();

        return view('clinic.agenda', [
            'clinic' => $clinic,
            'appointments' => $appointments,
            'nutritionists' => $nutritionists,
            'statuses' => AppointmentController::STATUSES,
            'filters' => [
                'nutriologo' => $nutritionistId,
                'estado' => $status,
                'paciente' => $search,
                'desde' => $from->toDateString(),
                'hasta' => $to?->toDateString(),
                'dia' => $activeDay,
            ],
            'quickDays' => collect(self::QUICK_DAYS)->map(fn ($offset) => today()->addDays($offset)),
            'hasFilters' => $nutritionistId || $status || $search !== '' || $quickDay
                || $request->filled('desde') || $request->filled('hasta'),
        ]);
    }

    private function date(mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
