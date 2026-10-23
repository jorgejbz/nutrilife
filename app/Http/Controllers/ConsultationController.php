<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ConsultationController extends Controller
{
    public function index(Request $request)
    {
        // ?paciente=<id> (desde la ficha del paciente o el detalle de una
        // consulta): solo pacientes propios; un id ajeno o inexistente no
        // filtra y se avisa en la vista.
        $patientFilter = $request->filled('paciente')
            ? Patient::withTrashed()->where('user_id', Auth::id())->find($request->integer('paciente'))
            : null;
        $invalidPatientFilter = $request->filled('paciente') && ! $patientFilter;

        $consultations = Consultation::where('user_id', Auth::id())
            ->when($patientFilter, fn ($query) => $query->where('patient_id', $patientFilter->id))
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
            'patientFilter',
            'invalidPatientFilter',
            'consultationsThisMonth',
            'consultationsToday',
            'patientsAttended'
        ));
    }


    /**
     * Detalle de una consulta («Ver consulta» del historial). Antes el botón
     * era un <button> sin acción y la ruta show no existía. Solo el
     * nutriólogo que la registró la abre; una consulta eliminada da 404.
     */
    public function show(Consultation $consulta)
    {
        abort_if($consulta->user_id !== Auth::id(), 403);

        $consulta->load(['patient', 'appointment']);

        // Citas del paciente el día de la próxima consulta, en cualquier estado
        // (la que se agendó en store y las que se hayan cancelado o cambiado).
        // has_linked: con pagos o consultas ligados no se puede eliminar
        // (misma regla que AppointmentController::destroy).
        $nextAppointments = $consulta->next_visit
            ? Appointment::where('user_id', Auth::id())
                ->where('patient_id', $consulta->patient_id)
                ->whereDate('appointment_at', $consulta->next_visit)
                ->withExists([
                    'payments as has_payments' => fn ($query) => $query->withTrashed(),
                    'consultations as has_consultations' => fn ($query) => $query->withTrashed(),
                ])
                ->orderBy('appointment_at')
                ->get()
            : collect();

        return view('consultations.show', [
            'consultation' => $consulta,
            'nextAppointments' => $nextAppointments,
        ]);
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

        // ?cita=<id> (desde el calendario de /citas): preselecciona esa cita
        // y su paciente. Solo si es una de las citas abiertas propias de arriba.
        $selectedAppointment = $appointments->firstWhere('id', request()->integer('cita'));

        return view('consultations.create', compact(
            'patients',
            'appointments',
            'selectedAppointment'
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

            // Una consulta se registra cuando ya ocurrió (hoy como máximo).
            'consultation_at' => ['required', 'date', 'after_or_equal:2000-01-01', 'before_or_equal:'.now()->endOfDay()->format('Y-m-d H:i:s')],
            'reason' => ['nullable', 'string', 'max:255'],
            'observations' => ['nullable', 'string', 'max:5000'],
            'recommendations' => ['nullable', 'string', 'max:5000'],
            'next_visit' => ['nullable', 'date', 'after_or_equal:today'],
            'next_visit_time' => ['nullable', 'date_format:H:i'],
        ], [
            'consultation_at.before_or_equal' => 'La consulta no puede tener fecha futura.',
            'consultation_at.after_or_equal' => 'Revisa la fecha de la consulta.',
            'consultation_at.date' => 'Escribe una fecha y hora válidas.',
            'patient_id.exists' => 'Selecciona un paciente válido de tu consultorio.',
            'appointment_id.exists' => 'La cita seleccionada no existe o ya fue completada/cancelada.',
            'next_visit.after_or_equal' => 'La próxima consulta no puede ser una fecha pasada.',
            'next_visit_time.date_format' => 'Escribe una hora válida para la próxima consulta.',
        ]);

        // La hora solo sirve para agendar la cita; consultations.next_visit es DATE.
        $nextVisitTime = $validated['next_visit_time'] ?? null;
        unset($validated['next_visit_time']);

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
        DB::transaction(function () use ($validated, $appointment, $nextVisitTime) {
            Consultation::create($validated);

            // Si la consulta provino de una cita, se marca como completada.
            $appointment?->update(['status' => 'Completada']);

            // El calendario, la lista de pacientes y el dashboard leen la
            // próxima cita de appointments; antes la próxima consulta solo
            // quedaba guardada en consultations.next_visit y no aparecía.
            if (! empty($validated['next_visit'])) {
                $nextAt = Carbon::parse($validated['next_visit'].' '.($nextVisitTime ?? '09:00'));

                // No duplicar si el paciente ya tiene una cita abierta ese día.
                $alreadyBooked = Appointment::where('user_id', $validated['user_id'])
                    ->where('patient_id', $validated['patient_id'])
                    ->whereIn('status', ['Pendiente', 'Confirmada'])
                    ->whereDate('appointment_at', $nextAt->toDateString())
                    ->exists();

                if (! $alreadyBooked) {
                    Appointment::create([
                        'user_id' => $validated['user_id'],
                        'patient_id' => $validated['patient_id'],
                        'appointment_at' => $nextAt,
                        'reason' => 'Seguimiento',
                        'status' => 'Pendiente',
                    ]);
                }
            }
        });

        return redirect()
            ->route('consultas.index')
            ->with('success', 'Consulta registrada correctamente.');
    }
}