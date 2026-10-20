<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
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
            'includesTrial' => (bool) config('nutrilife.features.'.$feature.'.trial', false),
            'accountType' => Subscription::accountTypeFor($user),
            'isAdmin' => (bool) $user?->isAdmin(),
        ], Response::HTTP_FORBIDDEN);
    }
}
