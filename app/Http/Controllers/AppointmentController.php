<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;


class AppointmentController extends Controller
{
    /** Valores de appointments.status (VARCHAR, por defecto 'Pendiente'). */
    public const STATUSES = ['Pendiente', 'Confirmada', 'Completada', 'Cancelada'];

    public function index()
    {
        $appointments = Appointment::where('user_id', Auth::id())
            ->with('patient')
            ->orderBy('appointment_at')
            ->get();

        return view('appointments.index', compact('appointments'));
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

    Appointment::create($validated);

    return redirect()
        ->route('citas.index')
        ->with('success', 'Cita registrada correctamente.');
}



    public function cancel(Appointment $cita)
{
    abort_if($cita->user_id !== Auth::id(), 403);

    // Igual que en el calendario: solo se cancelan citas abiertas.
    if (! in_array($cita->status, ['Pendiente', 'Confirmada'], true)) {
        return redirect()
            ->route('citas.index')
            ->with('error', 'Solo se pueden cancelar citas pendientes o confirmadas.');
    }

    $cita->update([
        'status' => 'Cancelada',
    ]);

    return redirect()
        ->route('citas.index')
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
        return redirect()
            ->route('citas.index')
            ->with('error', 'No se puede eliminar la cita porque tiene consultas o pagos registrados. Puedes cancelarla en su lugar.');
    }

    $cita->delete();

    return redirect()
        ->route('citas.index')
        ->with('success', 'Cita eliminada definitivamente.');
}
}