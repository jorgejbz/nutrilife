<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Herramientas de consulta (pacientes, citas, planes…): solo cuentas de
 * nutriólogo. El administrador global no atiende pacientes, así que se le
 * manda a su panel. Uso: ->middleware('nutritionist').
 */
class EnsureNutritionist
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sección solo para cuentas de nutriólogo.'], Response::HTTP_FORBIDDEN);
            }

            return redirect()->route('admin.dashboard');
        }

        abort_unless($user?->isNutritionist(), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
