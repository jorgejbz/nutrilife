<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea una sección completa si el plan del nutriólogo no la incluye.
 * Uso en rutas: ->middleware('feature:diet_plans').
 *
 * Es la barrera del lado del servidor: ocultar la opción del menú no basta,
 * así que cualquier petición (GET, POST, PATCH, DELETE, AJAX o URL escrita a
 * mano) pasa por aquí. Responde 403:
 *  - JSON para peticiones AJAX/API.
 *  - Pantalla "función no incluida en tu plan" para el navegador, con el
 *    plan actual y los planes que la incluyen.
 */
class EnsureFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if ($user && $user->hasFeature($feature)) {
            return $next($request);
        }

        $label = (string) config('nutrilife.features.'.$feature.'.label', 'Esta sección');
        $plans = Subscription::plansWithFeature($feature);
        $message = $label.' está disponible en los planes '.implode(' y ', $plans).'.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], Response::HTTP_FORBIDDEN);
        }

        return response()->view('partials.feature-locked', [
            'featureLabel' => $label,
            'plans' => $plans,
            // Prueba vigente de un paquete sin la función: se ofrece probar
            // uno que sí la tenga (p. ej. Esencial → Pro) sin esperar.
            'trialSwitchPlan' => $this->trialSwitchPlan($user, $plans),
            'accountType' => Subscription::accountTypeFor($user),
            'isAdmin' => (bool) $user?->isAdmin(),
        ], Response::HTTP_FORBIDDEN);
    }

    /** Paquete de prueba que incluye la función, si el usuario está en prueba vigente. */
    private function trialSwitchPlan($user, array $plans): ?SubscriptionPlan
    {
        $subscription = $user?->latestSubscription;

        if (! $subscription || ! $subscription->isTrial() || $subscription->trialHasExpired()) {
            return null;
        }

        $name = collect(Subscription::trialPlanNames())->first(fn ($n) => in_array($n, $plans, true));

        return $name ? Subscription::trialPlanFor($name) : null;
    }
}
