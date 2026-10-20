<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prueba gratuita y acceso al panel de los nutriólogos.
 *
 *  1. Sin suscripción (cuenta anterior a este flujo o planes cargados
 *     después): se crea la prueba en ese momento.
 *  2. Prueba vencida (inicio real + config nutrilife.trial_days):
 *     status en_prueba → vencida.
 *  3. Sin prueba vigente ni periodo pagado vigente: se redirige a la
 *     pantalla de contratación (subscription.plans). La revisión es en el
 *     servidor, en cada petición: no depende de ocultar botones.
 *
 * Rutas que nunca se bloquean (para poder contratar y salir):
 * self::ALWAYS_ALLOWED. La pantalla de contratación está ahí, así que no
 * hay redirecciones infinitas. logout vive en routes/auth.php, fuera de
 * este middleware.
 */
class EnsureTrialSubscription
{
    /** Nombres de ruta accesibles aunque no haya paquete vigente. */
    public const ALWAYS_ALLOWED = [
        'subscription.plans',
        'subscription.checkout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isNutritionist()) {
            return $next($request);
        }

        $subscription = $user->latestSubscription;

        if (! $subscription) {
            $subscription = Subscription::startTrialFor($user);
            $user->unsetRelation('latestSubscription');
        } elseif ($subscription->trialHasExpired()) {
            $subscription->update(['status' => Subscription::STATUS_EXPIRED]);
        }

        // Sin planes en la BD no hay nada que contratar: no se bloquea.
        if (! $subscription || $subscription->grantsPanelAccess()) {
            return $next($request);
        }

        if ($request->routeIs(...self::ALWAYS_ALLOWED)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tu periodo de prueba o tu suscripción terminó. Elige un paquete para continuar.',
                'plans_url' => route('subscription.plans'),
            ], Response::HTTP_PAYMENT_REQUIRED);
        }

        return redirect()->route('subscription.plans');
    }
}
