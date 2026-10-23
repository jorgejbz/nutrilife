<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;


class AppointmentController extends Controller
{
    /** Valores de appointments.status (VARCHAR, por defecto 'Pendiente'). */
    public const STATUSES = ['Pendiente', 'Confirmada', 'Completada', 'Cancelada'];

    public function index(Request $request)
    {
        // La cuenta principal de una clínica ve en su calendario las citas de
        // todo su equipo (con el nutriólogo de cada una); cancelar o eliminar
        // sigue siendo solo de quien agendó la cita. Los demás, solo las suyas.
        $user = Auth::user();
        $isClinic = $user->isClinic();
        $nutritionistIds = $isClinic ? $user->clinicNutritionistIds() : [$user->id];

        $appointments = Appointment::whereIn('user_id', $nutritionistIds)
            ->with(['patient', 'user:id,name,clinic_id'])
            ->orderBy('appointment_at')
            ->get();

        $nutritionists = $isClinic
            ? User::whereIn('id', $nutritionistIds)
                ->orderByRaw('id = ? desc', [$user->id])
                ->orderBy('name')
                ->get(['id', 'name', 'clinic_id'])
            : collect();

        // Día abierto en el panel "Detalle del día" (?dia=AAAA-MM-DD; lo
        // usan las redirecciones de cancelar/eliminar). Por defecto, hoy en
        // la zona horaria de la app (config/app.php), no la del navegador.
        $today = today()->toDateString();
        $selectedDay = self::validDay($request->query('dia')) ?? $today;

        return view('appointments.index', compact('appointments', 'today', 'selectedDay', 'isClinic', 'nutritionists'));
    }

    /** AAAA-MM-DD válido o null. */
    private static function validDay(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year) ? $value : null;
    }

    /**
     * Vuelve al calendario con el día de la cita abierto, o al detalle de la
     * consulta si la acción se hizo desde ahí (campo `consulta`; solo una
     * consulta propia, si no se ignora).
     */
    private function backToDay(Appointment $cita)
    {
        $consultationId = request()->integer('consulta');

        if ($consultationId && Consultation::where('user_id', Auth::id())->whereKey($consultationId)->exists()) {
            return redirect()->route('consultas.show', $consultationId);
        }

        return redirect()->route('citas.index', ['dia' => $cita->appointment_at->toDateString()]);
    }

    public function create()
    {
    $patients = \App\Models\Patient::where('user_id', Auth::id())
        ->where('active', true)
        ->orderBy('first_name')
        ->get();

    return view('appointments.create', compact('patients'));
}

    public function store(Request $request)
    {
    $validated = $request->validate([
        // FK a patients: paciente propio, vigente y activo (los mismos que
        // ofrece el formulario). Antes cualquier id existente pasaba la
        // validación y terminaba en un 404 de firstOrFail().
        'patient_id' => [
            'required',
            'integer',
            Rule::exists('patients', 'id')
                ->where('user_id', Auth::id())
                ->where('active', true)
                ->whereNull('deleted_at'),
        ],
        // Una cita Pendiente o Confirmada no puede quedar en el pasado;
        // las Completadas o Canceladas sí (registro de historial).
        'appointment_at' => [
            'required',
            'date',
            'after_or_equal:2000-01-01',
            Rule::when(
                in_array($request->input('status'), ['Pendiente', 'Confirmada'], true),
                ['after_or_equal:'.now()->startOfDay()->format('Y-m-d H:i:s')]
            ),
        ],
        'reason' => ['nullable', 'string', 'max:255'],
        'status' => ['required', Rule::in(self::STATUSES)],
        // El formulario limita (y cuenta) 1000 caracteres; antes el backend
        // aceptaba 2000. Columna: appointments.notes TEXT.
        'notes' => ['nullable', 'string', 'max:1000'],
    ], [
        'patient_id.exists' => 'Selecciona un paciente activo de tu consultorio.',
        'status.in' => 'Selecciona un estado válido.',
        'appointment_at.date' => 'Escribe una fecha y hora válidas.',
        'appointment_at.after_or_equal' => 'Una cita pendiente o confirmada no puede tener fecha pasada.',
    ]);

    $patient = \App\Models\Patient::where('id', $validated['patient_id'])
        ->where('user_id', Auth::id())
        ->firstOrFail();

    $validated['user_id'] = Auth::id();
    $validated['patient_id'] = $patient->id;

    $appointment = Appointment::create($validated);

    return $this->backToDay($appointment)
        ->with('success', 'Cita registrada correctamente.');
}



    public function confirm(Appointment $cita)
{
    abort_if($cita->user_id !== Auth::id(), 403);

    // Solo una cita pendiente puede pasar a confirmada.
    if ($cita->status !== 'Pendiente') {
        return $this->backToDay($cita)
            ->with('error', 'Solo se pueden confirmar citas pendientes.');
    }

    $cita->update([
        'status' => 'Confirmada',
    ]);

    return $this->backToDay($cita)
        ->with('success', 'Cita confirmada correctamente.');
}

    public function cancel(Appointment $cita)
{
    abort_if($cita->user_id !== Auth::id(), 403);

    // Igual que en el calendario: solo se cancelan citas abiertas.
    if (! in_array($cita->status, ['Pendiente', 'Confirmada'], true)) {
        return $this->backToDay($cita)
            ->with('error', 'Solo se pueden cancelar citas pendientes o confirmadas.');
    }

    $cita->update([
        'status' => 'Cancelada',
    ]);

    return $this->backToDay($cita)
        ->with('success', 'Cita cancelada correctamente.');
}

    public function destroy(Appointment $cita)
{
    abort_if($cita->user_id !== Auth::id(), 403);

    // Nueva BD: payments.appointment_id y consultations.appointment_id ya no
    // tienen ON DELETE SET NULL. Si la cita tiene registros ligados (incluso
    // eliminados con soft delete) MySQL rechazaría el DELETE, así que se
    // informa al usuario en vez de lanzar un error 500.
    $hasLinkedRecords = $cita->payments()->withTrashed()->exists()
        || $cita->consultations()->withTrashed()->exists();

    if ($hasLinkedRecords) {
        return $this->backToDay($cita)
            ->with('error', 'No se puede eliminar la cita porque tiene consultas o pagos registrados. Puedes cancelarla en su lugar.');
    }

    $back = $this->backToDay($cita);
    $cita->delete();

    return $back
        ->with('success', 'Cita eliminada definitivamente.');
}
}