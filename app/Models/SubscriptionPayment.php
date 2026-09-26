<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Cobros de la suscripción a la plataforma (tabla `subscription_payments`). */
class SubscriptionPayment extends Model
{
    protected $fillable = ['subscription_id', 'amount', 'paid_at', 'payment_method', 'status', 'reference'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
