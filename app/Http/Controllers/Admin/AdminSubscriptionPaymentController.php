<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;

/**
 * Cobros de la plataforma (subscription_payments) — /admin/cobros.
 *
 * Solo el administrador global confirma un cobro pendiente: un nutriólogo
 * nunca puede marcar como pagada su propia suscripción. Y solo se
 * confirman a mano los cobros MANUALES (payment_method = 'Manual'): si un
 * cobro viniera de una pasarela, su estado debe llegar de la pasarela
 * (webhook), no de un botón.
 */
class AdminSubscriptionPaymentController extends Controller
{
    /** Métodos que admiten confirmación manual. */
    public const MANUAL_METHODS = ['Manual'];

    public function index(Request $request)
    {
        $status = in_array($request->query('estado'), [SubscriptionPayment::STATUS_PAID, SubscriptionPayment::STATUS_PENDING], true) ? $request->query('estado') : null;
        $search = trim((string) $request->query('q'));

        $payments = SubscriptionPayment::query()
            ->with(['subscription.plan', 'subscription.user', 'statusChangedBy'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';

                $query->where(fn ($q) => $q
                    ->where('internal_reference', 'like', $like)
                    ->orWhere('reference', 'like', $like)
                    ->orWhereHas('subscription.plan', fn ($plan) => $plan->where('name', 'like', $like))
                    ->orWhereHas('subscription.user', fn ($user) => $user
                        ->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)));
            })
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [SubscriptionPayment::STATUS_PENDING])
            ->orderByDesc('paid_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.payments', [
            'payments' => $payments,
            'filters' => ['estado' => $status, 'q' => $search],
            'manualMethods' => self::MANUAL_METHODS,
            'totals' => [
                'paid' => SubscriptionPayment::where('status', SubscriptionPayment::STATUS_PAID)->count(),
                'pending' => SubscriptionPayment::where('status', SubscriptionPayment::STATUS_PENDING)->count(),
                'revenue' => (float) SubscriptionPayment::where('status', SubscriptionPayment::STATUS_PAID)->sum('amount'),
            ],
        ]);
    }

    public function complete(Request $request, SubscriptionPayment $subscriptionPayment)
    {
        if ($subscriptionPayment->status !== SubscriptionPayment::STATUS_PENDING) {
            return back()->with('error', 'Solo se confirman cobros pendientes.');
        }

        if (! in_array($subscriptionPayment->payment_method, self::MANUAL_METHODS, true)) {
            return back()->with('error', 'Este cobro no es manual: su estado debe confirmarlo la pasarela de pago.');
        }

        $subscriptionPayment->forceFill([
            'status' => SubscriptionPayment::STATUS_PAID,
            'paid_at' => now(),
            'status_changed_by' => $request->user()->id,
            'status_changed_at' => now(),
        ])->save();

        AuditLog::record($request->user(), 'subscription_payment.completed', $subscriptionPayment, [
            'amount' => (string) $subscriptionPayment->amount,
            'reference' => $subscriptionPayment->internal_reference,
            'external_reference' => $subscriptionPayment->reference,
            'account' => $subscriptionPayment->subscription?->user?->email,
        ]);

        return back()->with('success', 'Cobro '.$subscriptionPayment->internal_reference.' confirmado.');
    }
}
