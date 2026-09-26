<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            'patient_id' => [
                'required',
                'exists:patients,id'
            ],

            'appointment_id' => [
                'nullable',
                'exists:appointments,id'
            ],

            'consultation_at' => [
                'required',
                'date'
            ],

            'reason' => [
                'nullable',
                'string',
                'max:255'
            ],

            'observations' => [
                'nullable',
                'string',
                'max:5000'
            ],

            'recommendations' => [
                'nullable',
                'string',
                'max:5000'
            ],

            'next_visit' => [
                'nullable',
                'date',
                'after_or_equal:today'
            ],
        ]);


        // Verificar que el paciente pertenece al nutriólogo
        $patient = Patient::where('id', $validated['patient_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();


        // Si seleccionaron una cita
        if (!empty($validated['appointment_id'])) {

            $appointment = Appointment::where(
                'id',
                $validated['appointment_id']
            )
                ->where('user_id', Auth::id())
                ->firstOrFail();


            // La cita debe pertenecer al mismo paciente
            abort_if(
                $appointment->patient_id !== $patient->id,
                422,
                'La cita seleccionada no pertenece al paciente.'
            );

        } else {

            $appointment = null;
        }


        $validated['user_id'] = Auth::id();
        $validated['patient_id'] = $patient->id;


        Consultation::create($validated);


        // Si la consulta provino de una cita,
        // la marcamos automáticamente como completada.
        if ($appointment) {

            $appointment->update([
                'status' => 'Completada'
            ]);
        }


        return redirect()
            ->route('consultas.index')
            ->with(
                'success',
                'Consulta registrada correctamente.'
            );
    }



}