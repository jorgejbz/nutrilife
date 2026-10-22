<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use App\Models\Appointment;

/**
 * Pagos de pacientes (tabla `payments`).
 *
 * Son registros MANUALES del nutriólogo (efectivo, transferencia, tarjeta
 * en su terminal…): NutriLife no cobra a los pacientes ni está conectado a
 * una pasarela, así que no hay una transacción externa que verificar. Por
 * eso quien confirma que el dinero llegó es el nutriólogo dueño del pago:
 *  - solo él puede editarlo o cambiar su estado (authorizeOwner, en el
 *    servidor; la clínica y los demás nutriólogos no);
 *  - el estado cambia SOLO con las acciones complete()/cancel(), nunca
 *    desde el formulario de edición;
 *  - cada cambio guarda quién y cuándo (status_changed_by / _at) y queda
 *    en la bitácora (audit_logs).
 *
 * Referencias: internal_reference (EFE-/TAR-/TRA-/OTR-AAAAMMDD-NNNNNN) la
 * genera el servidor (PaymentReferenceService vía HasInternalReference) y
 * no llega desde el formulario; `reference` es la referencia EXTERNA
 * opcional que escribe el nutriólogo (clave de rastreo, folio de terminal).
 */
class PaymentController extends Controller
{
    /** Valores de payments.payment_method (VARCHAR, por defecto 'Efectivo'). */
    public const METHODS = ['Efectivo', 'Transferencia', 'Tarjeta', 'Otro'];

    /** Valores de payments.status (VARCHAR, por defecto 'Pagado'). */
    public const STATUSES = [Payment::STATUS_PAID, Payment::STATUS_PENDING, Payment::STATUS_CANCELLED];

    public function index(Request $request)
    {
        $status = in_array($request->query('estado'), self::STATUSES, true) ? $request->query('estado') : null;
        $method = in_array($request->query('metodo'), self::METHODS, true) ? $request->query('metodo') : null;
        $search = trim((string) $request->query('q'));

        $payments = Payment::where('user_id', Auth::id())
            ->with(['patient', 'statusChangedBy'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($method, fn ($query) => $query->where('payment_method', $method))
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';

                $query->where(function ($query) use ($like, $search) {
                    $query->where('concept', 'like', $like)
                        ->orWhere('internal_reference', 'like', $like)
                        ->orWhere('reference', 'like', $like)
                        // Nombre del paciente: cada palabra en nombre o apellidos.
                        ->orWhereHas('patient', function ($patient) use ($search) {
                            $patient->withTrashed();

                            foreach (preg_split('/\s+/', $search) as $word) {
                                $patient->where(fn ($name) => $name
                                    ->where('first_name', 'like', '%'.$word.'%')
                                    ->orWhere('last_name', 'like', '%'.$word.'%'));
                            }
                        });
                });
            })
            // Pendientes primero: son los que hay que atender.
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [Payment::STATUS_PENDING])
            ->orderByDesc('paid_at')
            ->paginate(20)
            ->withQueryString();

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
            'pendingPayments',
            'status',
            'method',
            'search'
        ));
    }


    public function create()
    {
        return view('payments.create', $this->formData());
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
        ] + $this->rules() + [
            'status' => ['required', Rule::in(self::STATUSES)],
        ], $this->messages());

        $this->ensureAppointmentBelongsToPatient($validated['appointment_id'] ?? null, (int) $validated['patient_id']);

        $validated['user_id'] = Auth::id();

        $payment = Payment::create($validated);

        AuditLog::record($request->user(), 'payment.created', $payment, [
            'reference' => $payment->internal_reference,
            'method' => $payment->payment_method,
            'amount' => (string) $payment->amount,
            'status' => $payment->status,
        ]);

        return redirect()
            ->route('pagos.index')
            ->with('success', 'Pago registrado con la referencia '.$payment->internal_reference.'.');
    }


    public function show(Payment $pago)
    {
        $this->authorizeOwner($pago);

        $pago->load(['patient', 'appointment', 'statusChangedBy']);

        $history = AuditLog::where('subject_type', 'Payment')
            ->where('subject_id', $pago->id)
            ->with('user')
            ->latest('id')
            ->get();

        return view('payments.show', ['payment' => $pago, 'history' => $history]);
    }


    public function edit(Payment $pago)
    {
        $this->authorizeOwner($pago);

        return view('payments.create', $this->formData($pago) + ['payment' => $pago->load('patient')]);
    }


    /**
     * Edita los datos del pago. El paciente y el estado NO cambian aquí:
     * el estado tiene sus propias acciones (complete / cancel).
     */
    public function update(Request $request, Payment $pago)
    {
        $this->authorizeOwner($pago);

        $validated = $request->validate($this->rules(), $this->messages());

        $this->ensureAppointmentBelongsToPatient($validated['appointment_id'] ?? null, (int) $pago->patient_id);

        $previousMethod = $pago->payment_method;
        $pago->fill($validated);
        $changes = array_keys($pago->getDirty());
        $message = $changes ? 'Pago actualizado correctamente.' : 'No hubo cambios que guardar.';

        if ($changes) {
            // HasInternalReference decide si la referencia cambia (solo un
            // pago pendiente que cambia a un método con otro prefijo).
            $pago->save();

            $details = ['fields' => $changes, 'reference' => $pago->internal_reference];

            if (in_array('payment_method', $changes, true)) {
                $details['method_from'] = $previousMethod;
                $details['method_to'] = $pago->payment_method;
            }

            if ($pago->previousInternalReference) {
                $details['reference_from'] = $pago->previousInternalReference;
                $details['reference_to'] = $pago->internal_reference;
                $message = 'Pago actualizado. Por el cambio de método, su nueva referencia es '.$pago->internal_reference
                    .' (la anterior, '.$pago->previousInternalReference.', queda en el historial).';
            } elseif (in_array('payment_method', $changes, true) && ! $pago->isPending()) {
                $details['reference_kept'] = true;
                $message = 'Pago actualizado. Su referencia '.$pago->internal_reference.' se conserva porque el pago ya no está pendiente.';
            }

            AuditLog::record($request->user(), 'payment.updated', $pago, $details);
        }

        return redirect()
            ->route('pagos.show', $pago)
            ->with('success', $message);
    }


    /**
     * Pendiente → Pagado. La fecha de pago pasa a ser el momento en que se
     * confirma (es cuando se recibió el dinero), salvo que se indique otra.
     */
    public function complete(Request $request, Payment $pago)
    {
        $this->authorizeOwner($pago);

        $validated = $request->validate([
            'paid_at' => ['nullable', 'date', 'after_or_equal:2000-01-01', 'before_or_equal:'.now()->endOfDay()->format('Y-m-d H:i:s')],
        ], [
            'paid_at.before_or_equal' => 'La fecha del pago no puede ser futura.',
        ]);

        return $this->changeStatus($request, $pago, Payment::STATUS_PAID, 'payment.completed', 'Pago marcado como completado.', $validated['paid_at'] ?? now());
    }


    /** Pendiente → Cancelado (el paciente ya no pagará ese cargo). */
    public function cancel(Request $request, Payment $pago)
    {
        $this->authorizeOwner($pago);

        return $this->changeStatus($request, $pago, Payment::STATUS_CANCELLED, 'payment.cancelled', 'Pago cancelado.');
    }


    private function changeStatus(Request $request, Payment $payment, string $status, string $action, string $message, $paidAt = null)
    {
        // Solo se resuelven pagos pendientes; un pago ya cobrado o cancelado
        // no se "reabre" desde aquí.
        if (! $payment->isPending()) {
            return back()->with('error', 'Solo se puede cambiar el estado de un pago pendiente.');
        }

        $previous = $payment->status;

        $payment->forceFill(array_filter([
            'status' => $status,
            'paid_at' => $paidAt,
            'status_changed_by' => $request->user()->id,
            'status_changed_at' => now(),
        ], fn ($value) => $value !== null))->save();

        AuditLog::record($request->user(), $action, $payment, [
            'from' => $previous,
            'to' => $status,
            'amount' => (string) $payment->amount,
            'reference' => $payment->internal_reference,
        ]);

        return back()->with('success', $message);
    }


    /** Solo el nutriólogo que registró el pago (404 para no revelar pagos ajenos). */
    private function authorizeOwner(Payment $payment): void
    {
        abort_if($payment->user_id !== Auth::id(), 404);
    }


    private function formData(?Payment $payment = null): array
    {
        $patients = Patient::where('user_id', Auth::id())
            ->where('active', true)
            ->orderBy('first_name')
            ->get();

        // Citas para vincular el cobro (payments.appointment_id, opcional):
        // propias, no canceladas y de pacientes vigentes. Al editar, solo
        // las del paciente del pago.
        $appointments = Appointment::where('user_id', Auth::id())
            ->where('status', '!=', 'Cancelada')
            ->whereHas('patient', fn ($query) => $query->whereNull('patients.deleted_at'))
            ->when($payment, fn ($query) => $query->where('patient_id', $payment->patient_id))
            ->orderByDesc('appointment_at')
            ->get();

        return compact('patients', 'appointments');
    }


    /** Campos editables (alta y edición). */
    private function rules(): array
    {
        return [
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
            // Referencia EXTERNA (opcional). La interna no se acepta del
            // formulario: no está en estas reglas ni es asignable en masa.
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }


    private function messages(): array
    {
        return [
            'patient_id.exists' => 'Selecciona un paciente válido de tu consultorio.',
            'appointment_id.exists' => 'La cita seleccionada no existe.',
            'amount.decimal' => 'El monto admite como máximo 2 decimales.',
            'amount.max' => 'El monto excede el máximo permitido.',
            'paid_at.date' => 'Escribe una fecha y hora válidas.',
            'paid_at.before_or_equal' => 'La fecha del pago no puede ser futura.',
            'paid_at.after_or_equal' => 'Revisa la fecha del pago.',
        ];
    }


    /** La cita, si se eligió, debe ser del mismo paciente. */
    private function ensureAppointmentBelongsToPatient($appointmentId, int $patientId): void
    {
        if (empty($appointmentId)) {
            return;
        }

        $appointment = Appointment::where('user_id', Auth::id())
            ->findOrFail($appointmentId);

        if ($appointment->patient_id !== $patientId) {
            throw ValidationException::withMessages([
                'appointment_id' => 'La cita seleccionada pertenece a otro paciente.',
            ]);
        }
    }
}
