<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ConsultationController extends Controller
{
    public function index()
    {
        $consultations = Consultation::where('user_id', Auth::id())
            ->with('patient')
            ->orderByDesc('consultation_at')
            ->get();

        $consultationsThisMonth = Consultation::where('user_id', Auth::id())
            ->whereYear('consultation_at', now()->year)
            ->whereMonth('consultation_at', now()->month)
            ->count();

        $consultationsToday = Consultation::where('user_id', Auth::id())
            ->whereDate('consultation_at', today())
            ->count();

        $patientsAttended = Consultation::where('user_id', Auth::id())
            ->distinct()
            ->count('patient_id');

        return view('consultations.index', compact(
            'consultations',
            'consultationsThisMonth',
            'consultationsToday',
            'patientsAttended'
        ));
    }


    public function create()
    {
        $patients = Patient::where('user_id', Auth::id())
            ->where('active', true)
            ->orderBy('first_name')
            ->get();

        $appointments = Appointment::where('user_id', Auth::id())
            ->whereIn('status', [
                'Pendiente',
                'Confirmada'
            ])
            // Solo citas de pacientes vigentes (patients usa borrado lógico)
            ->whereHas('patient', fn ($query) => $query->whereNull('patients.deleted_at'))
            ->with('patient')
            ->orderBy('appointment_at')
            ->get();

        return view('consultations.create', compact(
            'patients',
            'appointments'
        ));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            // FK a patients: debe ser un paciente del nutriólogo y no eliminado.
            'patient_id' => [
                'required',
                'integer',
                Rule::exists('patients', 'id')
                    ->where('user_id', Auth::id())
                    ->whereNull('deleted_at'),
            ],

            // FK opcional a appointments: cita propia y aún abierta
            // (el formulario solo ofrece citas Pendiente / Confirmada).
            'appointment_id' => [
                'nullable',
                'integer',
                Rule::exists('appointments', 'id')
                    ->where('user_id', Auth::id())
                    ->whereIn('status', ['Pendiente', 'Confirmada']),
            ],

            'consultation_at' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'observations' => ['nullable', 'string', 'max:5000'],
            'recommendations' => ['nullable', 'string', 'max:5000'],
            'next_visit' => ['nullable', 'date', 'after_or_equal:today'],
        ], [
            'patient_id.exists' => 'Selecciona un paciente válido de tu consultorio.',
            'appointment_id.exists' => 'La cita seleccionada no existe o ya fue completada/cancelada.',
            'next_visit.after_or_equal' => 'La próxima consulta no puede ser una fecha pasada.',
        ]);

        $appointment = null;

        if (! empty($validated['appointment_id'])) {
            $appointment = Appointment::where('user_id', Auth::id())
                ->findOrFail($validated['appointment_id']);

            // La cita debe pertenecer al mismo paciente. Antes era un abort(422)
            // que mostraba una página de error; ahora regresa al formulario
            // conservando lo capturado.
            if ((int) $appointment->patient_id !== (int) $validated['patient_id']) {
                throw ValidationException::withMessages([
                    'appointment_id' => 'La cita seleccionada pertenece a otro paciente.',
                ]);
            }
        }

        $validated['user_id'] = Auth::id();

        // Crear la consulta y completar la cita deben ocurrir juntos.
        DB::transaction(function () use ($validated, $appointment) {
            Consultation::create($validated);

            // Si la consulta provino de una cita, se marca como completada.
            $appointment?->update(['status' => 'Completada']);
        });

        return redirect()
            ->route('consultas.index')
            ->with('success', 'Consulta registrada correctamente.');
    }
}