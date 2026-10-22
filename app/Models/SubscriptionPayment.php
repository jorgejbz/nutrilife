<?php

namespace App\Models;

use App\Models\Concerns\HasInternalReference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Cobros de la suscripción a la plataforma (tabla `subscription_payments`). */
class SubscriptionPayment extends Model
{
    // internal_reference: referencia interna (ver HasInternalReference).
    use HasInternalReference;

    protected $fillable = ['subscription_id', 'amount', 'paid_at', 'payment_method', 'status', 'reference'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'status_changed_at' => 'datetime',
        ];
    }

    /** subscription_payments.status */
    public const STATUS_PAID = 'pagado';

    public const STATUS_PENDING = 'pendiente';

    /** Quién confirmó el cobro (solo el administrador global). */
    public function statusChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
