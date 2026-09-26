<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class AppointmentController extends Controller
{
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
        'patient_id' => ['required', 'exists:patients,id'],
        'appointment_at' => ['required', 'date'],
        'reason' => ['nullable', 'string', 'max:255'],
        'status' => ['required', 'in:Pendiente,Confirmada,Cancelada,Completada'],
        'notes' => ['nullable', 'string', 'max:2000'],
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