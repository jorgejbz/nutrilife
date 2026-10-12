<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garantiza que cada nutriólogo tenga su prueba gratuita de 7 días y
 * mantiene su estado al día. Se aplica a las rutas del panel.
 *
 *  - Sin suscripción (p. ej. se registró cuando aún no había planes):
 *    se le crea la prueba en ese momento.
 *  - Prueba cuya fecha ya pasó: status en_prueba → vencida.
 *
 * No bloquea el acceso: solo deja la información correcta para que el
 * panel muestre el aviso (ver Subscription::panelStatusFor).
 */
class EnsureTrialSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isNutritionist()) {
            $subscription = $user->latestSubscription;

            if (! $subscription) {
                if (Subscription::startTrialFor($user)) {
                    $user->unsetRelation('latestSubscription');
                }
            } elseif ($subscription->isTrial() && $subscription->trialDaysRemaining() < 0) {
                $subscription->update(['status' => Subscription::STATUS_EXPIRED]);
            }
        }

        return $next($request);
    }
}
