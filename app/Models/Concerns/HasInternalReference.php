<?php

namespace App\Models\Concerns;

use App\Services\PaymentReferenceService;

/**
 * Referencia interna de NutriLife (columna internal_reference) para los
 * modelos de pago: Payment y SubscriptionPayment. Requiere las constantes
 * STATUS_PENDING y la columna payment_method.
 *
 * POLÍTICA (igual en todos los módulos):
 * - Al crear: el servidor siempre la genera según el método; cualquier
 *   valor que llegue de fuera se ignora.
 * - Nunca se cambia a mano: un cambio directo de internal_reference se
 *   descarta al guardar.
 * - Pago PENDIENTE que cambia a un método con otro prefijo (p. ej.
 *   efectivo → transferencia): recibe una referencia NUEVA con el prefijo
 *   correcto; la anterior queda en $previousInternalReference para la
 *   bitácora y no se reutiliza.
 * - Pago PAGADO o CANCELADO: la referencia es histórica y no cambia, aunque
 *   se corrijan el método u otros campos (la bitácora guarda el cambio).
 */
trait HasInternalReference
{
    /** Referencia que tenía antes de regenerarse en este guardado (o null). */
    public ?string $previousInternalReference = null;

    protected static function bootHasInternalReference(): void
    {
        static::creating(function ($payment) {
            $payment->internal_reference = PaymentReferenceService::generate($payment->payment_method);
        });

        static::updating(function ($payment) {
            $original = $payment->getOriginal('internal_reference');
            $payment->previousInternalReference = null;

            if ($payment->isDirty('internal_reference')) {
                $payment->internal_reference = $original;
            }

            // Registro anterior al sistema de referencias sin backfill todavía.
            if (blank($original)) {
                $payment->internal_reference = PaymentReferenceService::generate($payment->payment_method);

                return;
            }

            $wasPending = $payment->getOriginal('status') === static::STATUS_PENDING;
            $prefixChanged = $payment->isDirty('payment_method')
                && PaymentReferenceService::prefixFor($payment->payment_method) !== PaymentReferenceService::prefixOf($original);

            if ($wasPending && $prefixChanged) {
                $payment->previousInternalReference = $original;
                $payment->internal_reference = PaymentReferenceService::generate($payment->payment_method);
            }
        });
    }

    /** Prefijo (EFE, TAR, TRA, SIM, PAS, OTR) de la referencia guardada. */
    public function referencePrefix(): string
    {
        return PaymentReferenceService::prefixOf($this->internal_reference);
    }

    public function referenceMethodLabel(): string
    {
        return PaymentReferenceService::labelFor($this->referencePrefix());
    }
}
