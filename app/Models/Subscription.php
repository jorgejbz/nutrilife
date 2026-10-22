<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
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

    /** Valor por defecto si falta config('nutrilife.trial_days'). */
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

        // created_at guarda la fecha y hora reales del inicio; las columnas
        // DATE (starts_at / current_period_ends_at) son solo informativas.
        $startedAt = now();

        return static::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => self::STATUS_TRIAL,
            'starts_at' => $startedAt->toDateString(),
            'current_period_ends_at' => $startedAt->copy()->addDays(self::trialDays())->toDateString(),
        ]);
    }

    /** Duración de la prueba (config/nutrilife.php → trial_days). */
    public static function trialDays(): int
    {
        return max((int) config('nutrilife.trial_days', self::TRIAL_DAYS), 0);
    }

    /**
     * Momento exacto en que vence la prueba: inicio real (created_at) +
     * trial_days. Si la fila no tiene created_at (insertada a mano), se usa
     * el inicio del día de starts_at. Nunca después del final del día
     * current_period_ends_at.
     */
    public function trialEndsAt(): Carbon
    {
        $start = $this->created_at
            ? $this->created_at->copy()
            : Carbon::parse($this->starts_at)->startOfDay();

        $end = $start->addDays(self::trialDays());

        // Si alguien acortó la prueba a mano (current_period_ends_at), manda
        // la fecha más temprana.
        if ($this->current_period_ends_at) {
            $limit = $this->current_period_ends_at->copy()->endOfDay();

            if ($limit->lt($end)) {
                return $limit;
            }
        }

        return $end;
    }

    /** ¿La prueba ya venció? (solo aplica a status en_prueba) */
    public function trialHasExpired(): bool
    {
        return $this->isTrial() && ! now()->lt($this->trialEndsAt());
    }

    /**
     * Días completos que le quedan a la prueba (0 = vence en menos de 24 h,
     * negativo = ya venció).
     */
    public function trialDaysRemaining(): int
    {
        if ($this->trialHasExpired()) {
            return -1;
        }

        return (int) floor(now()->diffInSeconds($this->trialEndsAt(), true) / 86400);
    }

    /**
     * ¿El periodo pagado sigue vigente? (activa, o cancelada que todavía
     * no llega al final del periodo que ya pagó)
     */
    public function hasPaidAccess(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_CANCELLED], true)
            && $this->current_period_ends_at !== null
            && ! $this->current_period_ends_at->copy()->endOfDay()->isPast();
    }

    /**
     * ¿Puede usar el panel? Prueba vigente o periodo pagado vigente.
     * Si no, el middleware lo manda a la pantalla de contratación.
     */
    public function grantsPanelAccess(): bool
    {
        return $this->isTrial() ? ! $this->trialHasExpired() : $this->hasPaidAccess();
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
                'ends_at' => $subscription->trialEndsAt(),
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

    /**
     * ¿Esta suscripción da acceso a la función $feature
     * (config/nutrilife.php → features)?
     *
     * Se distingue el PLAN (Esencial, Pro, Consultorio) del ESTADO:
     *  - en_prueba  → solo si la función incluye la prueba ('trial' => true)
     *                 y la prueba no ha vencido.
     *  - activa     → si el plan está en 'plans' y el periodo pagado sigue
     *                 vigente (current_period_ends_at >= hoy).
     *  - cancelada  → igual que activa: conserva el acceso hasta que termine
     *                 el periodo que ya pagó.
     *  - vencida    → nunca.
     */
    public function grantsFeature(string $feature): bool
    {
        $config = config('nutrilife.features.'.$feature);

        if (! is_array($config)) {
            return false;
        }

        if ($this->isTrial()) {
            return (bool) ($config['trial'] ?? false) && ! $this->trialHasExpired();
        }

        if (! $this->hasPaidAccess()) {
            return false;
        }

        return in_array($this->plan?->name, (array) ($config['plans'] ?? []), true);
    }

    /**
     * Planes que incluyen la función (para mostrarlo en pantallas de aviso).
     *
     * @return list<string>
     */
    public static function plansWithFeature(string $feature): array
    {
        return array_values((array) config('nutrilife.features.'.$feature.'.plans', []));
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
     *   type: trial | expired | active | cancelled | admin | member
     */
    public static function accountTypeFor(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        if ($user->isAdmin()) {
            return ['type' => 'admin', 'label' => 'Administrador', 'plan' => null];
        }

        // Subperfil de clínica: sin suscripción propia; usa la licencia de su
        // consultorio (funciones del plan config nutrilife.clinic.member_plan).
        if ($user->isClinicMember()) {
            $clinic = $user->clinic;

            return [
                'type' => 'member',
                'label' => 'Nutriólogo · '.($clinic?->name ?? 'Clínica'),
                'plan' => (string) config('nutrilife.clinic.member_plan'),
                'license_active' => (bool) $clinic?->clinicLicenseActive(),
            ];
        }

        $subscription = $user->latestSubscription;

        if (! $subscription) {
            return null;
        }

        $plan = $subscription->plan?->name;
        $trialState = self::panelStatusFor($user)['state'] ?? null;

        if ($subscription->status === self::STATUS_ACTIVE && $subscription->hasPaidAccess()) {
            return ['type' => 'active', 'label' => 'Plan '.$plan, 'plan' => $plan];
        }

        if ($subscription->status === self::STATUS_CANCELLED && $subscription->hasPaidAccess()) {
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
