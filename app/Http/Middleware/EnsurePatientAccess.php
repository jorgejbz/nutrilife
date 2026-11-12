<?php

namespace App\Http\Middleware;

use App\Models\Patient;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * App móvil: el paciente con token solo pasa si su expediente sigue activo y
 * la cuenta de su nutriólogo está habilitada (mismas reglas que el panel).
 */
class EnsurePatientAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $patient = $request->user();

        if (! $patient instanceof Patient) {
            return response()->json(['message' => 'No autenticado.'], Response::HTTP_UNAUTHORIZED);
        }

        if ($reason = self::denyReason($patient)) {
            return response()->json(['message' => $reason], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }

    /** Motivo por el que el paciente no puede usar la app; null si todo está bien. */
    public static function denyReason(Patient $patient): ?string
    {
        if (! $patient->active) {
            return 'Tu expediente está inactivo. Comunícate con tu nutriólogo.';
        }

        $nutri = $patient->user;

        if (! $nutri || $nutri->isDisabled() || $nutri->isPendingActivation()) {
            return 'La cuenta de tu nutriólogo no está activa por ahora.';
        }

        if (config('nutrilife.mobile.require_active_subscription') && ! self::hasAccess($nutri)) {
            return 'Tu nutriólogo no tiene una suscripción vigente.';
        }

        return null;
    }

    /** Misma regla que EnsureTrialSubscription (sin crear pruebas ni cambiar estados). */
    private static function hasAccess(User $nutri): bool
    {
        if ($nutri->isClinicMember()) {
            return (bool) $nutri->clinic?->clinicLicenseActive();
        }

        $subscription = $nutri->latestSubscription;

        return $subscription === null || $subscription->grantsPanelAccess();
    }
}
