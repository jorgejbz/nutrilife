<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Suscripción de un nutriólogo a la plataforma (tabla `subscriptions`).
 *
 * Sustituye a las antiguas columnas users.trial_* / users.subscription_*.
 * status: en_prueba | activa | vencida | cancelada
 */
class Subscription extends Model
{
    public const STATUS_TRIAL = 'en_prueba';
    public const STATUS_ACTIVE = 'activa';
    public const STATUS_EXPIRED = 'vencida';
    public const STATUS_CANCELLED = 'cancelada';

    /** Duración de la prueba gratuita (mismo valor que el proyecto anterior). */
    public const TRIAL_DAYS = 7;

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'status',
        'starts_at',
        'current_period_ends_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'current_period_ends_at' => 'date',
            'cancelled_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function isTrial(): bool
    {
        return $this->status === self::STATUS_TRIAL;
    }

    /**
     * Crea la suscripción de prueba para un nutriólogo recién registrado.
     *
     * `subscription_plan_id` es obligatorio en la nueva BD, así que la
     * prueba se asocia al plan activo más económico. Si todavía no hay
     * ningún plan registrado NO se inventa uno: se devuelve null y el
     * registro continúa sin prueba.
     */
    public static function startTrialFor(User $user): ?self
    {
        $plan = SubscriptionPlan::where('active', true)
            ->orderBy('price')
            ->orderBy('id')
            ->first();

        if (! $plan) {
            return null;
        }

        $today = now()->startOfDay();

        return static::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => self::STATUS_TRIAL,
            'starts_at' => $today,
            'current_period_ends_at' => $today->copy()->addDays(self::TRIAL_DAYS),
        ]);
    }
}
