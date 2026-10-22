<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Contratación de un paquete.
 *
 * HOY ES UNA SIMULACIÓN (config nutrilife.checkout.simulated_payments):
 * no se cobra nada; se registra el cobro en subscription_payments con
 * payment_method = 'Simulado' y referencia SIM-…, para que el resto del
 * sistema (perfil, historial de cobros, acceso) funcione igual que con un
 * pago real.
 *
 * Para conectar una pasarela real: crear el cobro con la pasarela y llamar
 * a activate() solo cuando la pasarela confirme el pago (webhook).
 */
class PlanCheckout
{
    public static function isEnabled(): bool
    {
        return (bool) config('nutrilife.checkout.simulated_payments', true);
    }

    /**
     * @throws ValidationException
     */
    public static function purchase(User $user, SubscriptionPlan $plan): Subscription
    {
        if (! self::isEnabled()) {
            throw ValidationException::withMessages([
                'plan' => 'La contratación en línea no está disponible por ahora.',
            ]);
        }

        if (! $plan->active) {
            throw ValidationException::withMessages([
                'plan' => 'Este paquete ya no está disponible.',
            ]);
        }

        $current = $user->latestSubscription;

        if ($current && $current->hasPaidAccess()
            && $current->status === Subscription::STATUS_ACTIVE
            && (int) $current->subscription_plan_id === (int) $plan->id) {
            throw ValidationException::withMessages([
                'plan' => 'Ya tienes el paquete '.$plan->name.' activo.',
            ]);
        }

        $now = now();

        return self::activate($user, $plan, $current, self::periodEnd($plan, $now), [
            'payment_method' => (string) config('nutrilife.checkout.payment_method', 'Simulado'),
            'status' => SubscriptionPayment::STATUS_PAID,
            // Sin pasarela no hay referencia externa: la interna (SIM-…) la
            // genera SubscriptionPayment al guardarse (PaymentReferenceService).
        ]);
    }

    /** Cobro que se registra al asignar un plan desde el panel de administración. */
    public const ADMIN_PAYMENT_NONE = 'ninguno';

    public const ADMIN_PAYMENT_PENDING = SubscriptionPayment::STATUS_PENDING;

    public const ADMIN_PAYMENT_PAID = SubscriptionPayment::STATUS_PAID;

    /**
     * El administrador global asigna un plan a una cuenta principal (misma
     * lógica que la contratación: cierra la suscripción anterior y abre una
     * nueva). $payment:
     *  - 'ninguno'   → sin cobro (cortesía / ajuste).
     *  - 'pendiente' → cobro manual por confirmar (se confirma en /admin/cobros).
     *  - 'pagado'    → cobro manual ya recibido.
     * Nunca toca pasarelas externas: el método queda como "Manual".
     */
    public static function assignByAdmin(User $user, SubscriptionPlan $plan, User $admin, int $months, string $payment): Subscription
    {
        if (! $user->isNutritionist() || $user->isClinicMember()) {
            throw ValidationException::withMessages([
                'subscription_plan_id' => 'Solo las cuentas principales tienen plan propio; los subperfiles usan la licencia de su clínica.',
            ]);
        }

        $now = now();

        return self::activate($user, $plan, $user->latestSubscription, $now->copy()->addMonthsNoOverflow(max($months, 1)), $payment === self::ADMIN_PAYMENT_NONE ? null : [
            'payment_method' => 'Manual',
            'status' => $payment,
            // Sin método conocido: la referencia interna sale con prefijo OTR.
            'status_changed_by' => $payment === self::ADMIN_PAYMENT_PAID ? $admin->id : null,
            'status_changed_at' => $payment === self::ADMIN_PAYMENT_PAID ? $now : null,
        ]);
    }

    /**
     * @param  array{payment_method: string, status: string, status_changed_by?: ?int, status_changed_at?: mixed}|null  $payment
     */
    private static function activate(User $user, SubscriptionPlan $plan, ?Subscription $current, Carbon $periodEnd, ?array $payment): Subscription
    {
        return DB::transaction(function () use ($user, $plan, $current, $periodEnd, $payment) {
            $now = now();

            // Cambio de paquete: el anterior se cancela desde hoy.
            if ($current && $current->hasPaidAccess()) {
                $current->update([
                    'status' => Subscription::STATUS_CANCELLED,
                    'cancelled_at' => $now->toDateString(),
                    'current_period_ends_at' => $now->toDateString(),
                ]);
            } elseif ($current && $current->isTrial()) {
                // La prueba termina al contratar.
                $current->update([
                    'status' => Subscription::STATUS_EXPIRED,
                    'current_period_ends_at' => $now->toDateString(),
                ]);
            }

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => $now->toDateString(),
                'current_period_ends_at' => $periodEnd->toDateString(),
            ]);

            if ($payment !== null) {
                (new SubscriptionPayment)->forceFill([
                    'subscription_id' => $subscription->id,
                    'amount' => $plan->price,
                    'paid_at' => $now,
                    'status_changed_by' => null,
                    'status_changed_at' => null,
                ] + $payment)->save();
            }

            $user->unsetRelation('latestSubscription');

            return $subscription;
        });
    }

    public static function periodEnd(SubscriptionPlan $plan, Carbon $from): Carbon
    {
        return $plan->billing_period === SubscriptionPlan::PERIOD_YEARLY
            ? $from->copy()->addYearNoOverflow()
            : $from->copy()->addMonthNoOverflow();
    }
}
