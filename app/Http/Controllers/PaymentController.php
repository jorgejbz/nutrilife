<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use App\Models\Appointment;

class PaymentController extends Controller
{
    /** Valores de payments.payment_method (VARCHAR, por defecto 'Efectivo'). */
    public const METHODS = ['Efectivo', 'Transferencia', 'Tarjeta', 'Otro'];

    /** Valores de payments.status (VARCHAR, por defecto 'Pagado'). */
    public const STATUSES = ['Pagado', 'Pendiente', 'Cancelado'];

    public function index()
    {
        $payments = Payment::where('user_id', Auth::id())
            ->with('patient')
            ->orderByDesc('paid_at')
            ->get();

        $totalIncome = Payment::where('user_id', Auth::id())
            ->where('status', 'Pagado')
            ->sum('amount');

        $monthIncome = Payment::where('user_id', Auth::id())
            ->where('status', 'Pagado')
            ->whereYear('paid_at', now()->year)
            ->whereMonth('paid_at', now()->month)
            ->sum('amount');

        $paidPayments = Payment::where('user_id', Auth::id())
            ->where('status', 'Pagado')
            ->count();

        $pendingPayments = Payment::where('user_id', Auth::id())
            ->where('status', 'Pendiente')
            ->count();

        return view('payments.index', compact(
            'payments',
            'totalIncome',
            'monthIncome',
            'paidPayments',
            'pendingPayments'
        ));
    }


    public function create()
    {
        $patients = Patient::where('user_id', Auth::id())
            ->where('active', true)
            ->orderBy('first_name')
            ->get();

        // Citas para vincular el cobro (payments.appointment_id, opcional):
        // propias, no canceladas y de pacientes vigentes.
        $appointments = Appointment::where('user_id', Auth::id())
            ->where('status', '!=', 'Cancelada')
            ->whereHas('patient', fn ($query) => $query->whereNull('patients.deleted_at'))
            ->orderByDesc('appointment_at')
            ->get();

        return view('payments.create', compact('patients', 'appointments'));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            // FK a patients: paciente propio y vigente.
            'patient_id' => [
                'required',
                'integer',
                Rule::exists('patients', 'id')
                    ->where('user_id', Auth::id())
                    ->whereNull('deleted_at'),
            ],

            // FK opcional a appointments (antes el formulario no la ofrecía).
            'appointment_id' => [
                'nullable',
                'integer',
                Rule::exists('appointments', 'id')->where('user_id', Auth::id()),
            ],

            // payments.amount DECIMAL(10,2)
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],

            'paid_at' => ['required', 'date', 'after_or_equal:2000-01-01', 'before_or_equal:'.now()->endOfDay()->format('Y-m-d H:i:s')],

            // payments.concept admite NULL en la BD, pero el sistema ya lo
            // exigía; se conserva la regla de negocio existente.
            'concept' => ['required', 'string', 'max:255'],

            'payment_method' => ['required', Rule::in(self::METHODS)],
            'status' => ['required', Rule::in(self::STATUSES)],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'patient_id.exists' => 'Selecciona un paciente válido de tu consultorio.',
            'appointment_id.exists' => 'La cita seleccionada no existe.',
            'amount.decimal' => 'El monto admite como máximo 2 decimales.',
            'amount.max' => 'El monto excede el máximo permitido.',
            'paid_at.date' => 'Escribe una fecha y hora válidas.',
            'paid_at.before_or_equal' => 'La fecha del pago no puede ser futura.',
            'paid_at.after_or_equal' => 'Revisa la fecha del pago.',
        ]);

        // La cita, si se eligió, debe ser del mismo paciente.
        if (! empty($validated['appointment_id'])) {
            $appointment = Appointment::where('user_id', Auth::id())
                ->findOrFail($validated['appointment_id']);

            if ($appointment->patient_id !== (int) $validated['patient_id']) {
                throw ValidationException::withMessages([
                    'appointment_id' => 'La cita seleccionada pertenece a otro paciente.',
                ]);
            }
        }

        $validated['user_id'] = Auth::id();

        Payment::create($validated);

        return redirect()
            ->route('pagos.index')
            ->with('success', 'Pago registrado correctamente.');
    }



}