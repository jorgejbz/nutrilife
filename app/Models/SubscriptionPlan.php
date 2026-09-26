<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Planes de la plataforma (tabla `subscription_plans`). */
class SubscriptionPlan extends Model
{
    public const PERIOD_MONTHLY = 'mensual';
    public const PERIOD_YEARLY = 'anual';

    protected $fillable = ['name', 'price', 'billing_period', 'max_patients', 'active'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'max_patients' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
