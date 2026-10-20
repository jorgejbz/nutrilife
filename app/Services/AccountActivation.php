<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Activación de cuentas nuevas (simulada).
 *
 * Flujo:
 *  1. Registro → account_status = 'pendiente' y activates_at = ahora +
 *     config('nutrilife.account_activation.delay_minutes').
 *  2. Cada vez que el usuario intenta iniciar sesión o entra a una ruta
 *     privada se llama a refresh(): si ya llegó activates_at, la cuenta pasa
 *     a 'activa', se guarda activated_at y empieza su prueba gratuita.
 *     No depende de que la página siga abierta ni de tareas programadas.
 *
 * Esto NO es una verificación profesional: professional_verified_at no se
 * toca. Para conectar una validación real de cédula/título, reemplaza
 * schedule() por "pendiente sin fecha" y llama a activate() cuando el
 * servicio externo (o un administrador) apruebe la cuenta.
 */
class AccountActivation
{
    public static function delayMinutes(): int
    {
        return max((int) config('nutrilife.account_activation.delay_minutes', 5), 0);
    }

    /** Deja la cuenta pendiente y calcula cuándo se habilita. */
    public static function schedule(User $user, ?Carbon $from = null): void
    {
        $user->forceFill([
            'account_status' => User::ACCOUNT_PENDING,
            'activates_at' => ($from ?? now())->copy()->addMinutes(self::delayMinutes()),
            'activated_at' => null,
        ])->save();
    }

    /**
     * Revisa una cuenta pendiente y la activa si ya se cumplió el tiempo.
     * Devuelve true si la cuenta está activa al terminar.
     */
    public static function refresh(User $user): bool
    {
        if (! $user->isPendingActivation()) {
            return true;
        }

        if ($user->activates_at === null || $user->activates_at->isFuture()) {
            return false;
        }

        self::activate($user);

        return true;
    }

    /** Activa la cuenta y arranca la prueba gratuita desde este momento. */
    public static function activate(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->forceFill([
                'account_status' => User::ACCOUNT_ACTIVE,
                'activated_at' => now(),
            ])->save();

            if ($user->isNutritionist() && ! $user->subscriptions()->exists()) {
                Subscription::startTrialFor($user);
            }
        });

        $user->unsetRelation('latestSubscription');
    }
}
