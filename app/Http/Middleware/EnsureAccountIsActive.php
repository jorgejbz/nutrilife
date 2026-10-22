<?php

namespace App\Http\Middleware;

use App\Services\AccountActivation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea las rutas privadas a cuentas que siguen "pendientes".
 *
 * Revisa en el servidor (AccountActivation::refresh) si ya llegó la hora de
 * activación: si llegó, activa la cuenta y deja pasar; si no, cierra la
 * sesión y muestra la pantalla de "cuenta en preparación".
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Cuenta desactivada (por el administrador o por su clínica): la
        // sesión abierta se cierra en la siguiente petición.
        if ($user->isDisabled()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => AccountActivation::DISABLED_MESSAGE], Response::HTTP_FORBIDDEN);
            }

            return redirect()->route('login')->withErrors(['email' => AccountActivation::DISABLED_MESSAGE]);
        }

        if (AccountActivation::refresh($user)) {
            return $next($request);
        }

        $activatesAt = $user->activates_at?->toIso8601String();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Tu cuenta todavía no está habilitada.'], Response::HTTP_FORBIDDEN);
        }

        return redirect()->route('account.pending')->with('pending_until', $activatesAt);
    }
}
