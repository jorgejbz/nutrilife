<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

        return self::activate($user, $plan, $current);
    }

    private static function activate(User $user, SubscriptionPlan $plan, ?Subscription $current): Subscription
    {
        return DB::transaction(function () use ($user, $plan, $current) {
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
                'current_period_ends_at' => self::periodEnd($plan, $now)->toDateString(),
            ]);

            SubscriptionPayment::create([
                'subscription_id' => $subscription->id,
                'amount' => $plan->price,
                'paid_at' => $now,
                'payment_method' => (string) config('nutrilife.checkout.payment_method', 'Simulado'),
                'status' => 'pagado',
                'reference' => 'SIM-'.$now->format('Ymd').'-'.Str::upper(Str::random(6)),
            ]);

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
