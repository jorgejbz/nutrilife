<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PaymentController;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Pagos de pacientes de todo el consultorio (/clinica/pagos), solo lectura.
 * La clínica identifica cada pago por su referencia NutriLife y su método;
 * editarlo o cambiar su estado sigue siendo solo del nutriólogo que lo
 * registró (PaymentController::authorizeOwner). Solo pagos de la cuenta
 * principal y sus subperfiles: nunca de otra clínica.
 */
class ClinicPaymentController extends Controller
{
    public function index(Request $request)
    {
        $clinic = $request->user();
        $ids = $clinic->clinicNutritionistIds();
        $nutritionists = User::whereIn('id', $ids)->orderBy('name')->get(['id', 'name', 'clinic_id']);

        $nutritionistId = in_array($request->integer('nutriologo'), $ids, true) ? $request->integer('nutriologo') : null;
        $status = in_array($request->query('estado'), PaymentController::STATUSES, true) ? $request->query('estado') : null;
        $method = in_array($request->query('metodo'), PaymentController::METHODS, true) ? $request->query('metodo') : null;
        $search = trim((string) $request->query('q'));

        $payments = Payment::whereIn('user_id', $nutritionistId ? [$nutritionistId] : $ids)
            ->with(['patient', 'user:id,name,clinic_id'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($method, fn ($query) => $query->where('payment_method', $method))
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';

                $query->where(fn ($q) => $q
                    ->where('internal_reference', 'like', $like)
                    ->orWhere('reference', 'like', $like)
                    ->orWhere('concept', 'like', $like)
                    ->orWhereHas('patient', function ($patient) use ($search) {
                        $patient->withTrashed();

                        foreach (preg_split('/\s+/', $search) as $word) {
                            $patient->where(fn ($name) => $name
                                ->where('first_name', 'like', '%'.$word.'%')
                                ->orWhere('last_name', 'like', '%'.$word.'%'));
                        }
                    }));
            })
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [Payment::STATUS_PENDING])
            ->orderByDesc('paid_at')
            ->paginate(25)
            ->withQueryString();

        return view('clinic.payments', [
            'clinic' => $clinic,
            'payments' => $payments,
            'nutritionists' => $nutritionists,
            'statuses' => PaymentController::STATUSES,
            'methods' => PaymentController::METHODS,
            'filters' => ['nutriologo' => $nutritionistId, 'estado' => $status, 'metodo' => $method, 'q' => $search],
            'hasFilters' => $nutritionistId || $status || $method || $search !== '',
        ]);
    }
}
