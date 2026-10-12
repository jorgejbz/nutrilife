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

    /** Textos para mostrar cada estado de subscriptions.status. */
    public const STATUS_LABELS = [
        self::STATUS_TRIAL => 'En prueba',
        self::STATUS_ACTIVE => 'Activa',
        self::STATUS_EXPIRED => 'Vencida',
        self::STATUS_CANCELLED => 'Cancelada',
    ];

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
     * Crea la suscripción de prueba de un nutriólogo (status = en_prueba).
     *
     * `subscription_plan_id` es obligatorio en la BD, así que la prueba se
     * asocia al plan activo más económico (los planes los inserta
     * SubscriptionPlanSeeder). Si todavía no hay ningún plan NO se inventa
     * uno: se devuelve null y el middleware EnsureTrialSubscription la
     * creará en cuanto exista un plan.
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

    /**
     * Días que le quedan a la prueba (0 = hoy es el último día,
     * negativo = ya venció).
     */
    public function trialDaysRemaining(): int
    {
        return (int) now()->startOfDay()->diffInDays(
            $this->current_period_ends_at->copy()->startOfDay(),
            false
        );
    }

    /**
     * Estado de la prueba para mostrar en el panel, o null cuando no hay
     * nada que mostrar (suscripción activa = ya compró el sistema).
     *
     * @return array{state: string, days: int, ends_at: \Illuminate\Support\Carbon}|null
     *   state: 'trial'   → prueba en curso
     *          'expired' → la prueba terminó y no ha contratado un plan
     */
    public static function panelStatusFor(?User $user): ?array
    {
        $subscription = $user?->latestSubscription;

        if (! $subscription) {
            return null;
        }

        if ($subscription->isTrial()) {
            $days = $subscription->trialDaysRemaining();

            return [
                'state' => $days >= 0 ? 'trial' : 'expired',
                'days' => max($days, 0),
                'ends_at' => $subscription->current_period_ends_at,
            ];
        }

        // Una prueba vencida que nunca tuvo pagos sigue siendo "prueba terminada".
        if ($subscription->status === self::STATUS_EXPIRED && ! $subscription->payments()->exists()) {
            return [
                'state' => 'expired',
                'days' => 0,
                'ends_at' => $subscription->current_period_ends_at,
            ];
        }

        return null;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    /**
     * Tipo de cuenta del usuario para mostrarlo en el panel (barra superior
     * y perfil). Devuelve null si no hay nada que mostrar.
     *
     * @return array{type: string, label: string, plan: ?string}|null
     *   type: trial | expired | active | cancelled | admin
     */
    public static function accountTypeFor(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        if ($user->isAdmin()) {
            return ['type' => 'admin', 'label' => 'Administrador', 'plan' => null];
        }

        $subscription = $user->latestSubscription;

        if (! $subscription) {
            return null;
        }

        $plan = $subscription->plan?->name;
        $trialState = self::panelStatusFor($user)['state'] ?? null;

        if ($subscription->status === self::STATUS_ACTIVE) {
            return ['type' => 'active', 'label' => 'Plan '.$plan, 'plan' => $plan];
        }

        if ($subscription->status === self::STATUS_CANCELLED) {
            return ['type' => 'cancelled', 'label' => 'Plan cancelado', 'plan' => $plan];
        }

        if ($trialState === 'trial') {
            return ['type' => 'trial', 'label' => 'Prueba gratuita', 'plan' => null];
        }

        if ($trialState === 'expired') {
            return ['type' => 'expired', 'label' => 'Prueba terminada', 'plan' => null];
        }

        // Vencida después de haber pagado (no renovó)
        return ['type' => 'expired', 'label' => 'Suscripción vencida', 'plan' => $plan];
    }
}
