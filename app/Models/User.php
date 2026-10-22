<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Nutriólogo o administrador de NutriLife (tabla `users`).
 *
 * Cambios respecto a la BD anterior:
 *  - Se eliminaron las columnas de prueba/suscripción (trial_*, plan,
 *    subscription_*). Ahora viven en `subscriptions` (ver Subscription).
 *  - Nuevas columnas: role_id (FK roles, 1 = nutriólogo) y access_code
 *    (6 dígitos, único, NOT NULL). El código se genera solo al crear el
 *    usuario si no se proporciona uno.
 */
// account_status, activates_at, activated_at, professional_verified_at,
// role_id y clinic_id NO son asignables en masa: solo el servidor los cambia
// (AccountActivation, el registro, la clínica o el administrador con
// forceFill), así una petición manipulada no puede activar ni verificar una
// cuenta, volverse administrador ni unirse a otra clínica.
#[Fillable(['name', 'email', 'password', 'access_code', 'professional_license', 'degree_number'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** users.account_status */
    public const ACCOUNT_PENDING = 'pendiente';

    public const ACCOUNT_ACTIVE = 'activa';

    /** Desactivada por su clínica o por el administrador: no puede entrar. */
    public const ACCOUNT_DISABLED = 'desactivada';

    public const ACCOUNT_STATUS_LABELS = [
        self::ACCOUNT_PENDING => 'Pendiente de activación',
        self::ACCOUNT_ACTIVE => 'Activa',
        self::ACCOUNT_DISABLED => 'Desactivada',
    ];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->access_code)) {
                $user->access_code = static::generateAccessCode();
            }
        });
    }

    /**
     * Genera un código de acceso de 6 dígitos que no exista en `users`.
     */
    public static function generateAccessCode(): string
    {
        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (static::where('access_code', $code)->exists());

        return $code;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activates_at' => 'datetime',
            'activated_at' => 'datetime',
            'professional_verified_at' => 'datetime',
            'clinic_id' => 'integer',
        ];
    }

    /* ------------------------------------------------------------------
     | Relaciones
     * ------------------------------------------------------------------ */

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function dietPlans(): HasMany
    {
        return $this->hasMany(DietPlan::class);
    }

    /** Platillos propios (los del sistema tienen user_id NULL). */
    public function dietPlanDishes(): HasMany
    {
        return $this->hasMany(DietPlanDish::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Suscripción más reciente del nutriólogo (en prueba, activa, vencida…).
     */
    public function latestSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    /** Cuenta principal del consultorio (solo en subperfiles). */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'clinic_id');
    }

    /** Subperfiles de nutriólogo de esta clínica. */
    public function clinicMembers(): HasMany
    {
        return $this->hasMany(User::class, 'clinic_id');
    }

    /**
     * ¿Tiene información clínica registrada? patients, appointments,
     * consultations, payments y diet_plans referencian a users SIN borrado
     * en cascada, así que una cuenta con datos no se puede eliminar sin
     * perder expedientes (y MySQL lo rechazaría). Una clínica con
     * subperfiles tampoco (users.clinic_id es restrictOnDelete).
     */
    public function hasClinicalData(): bool
    {
        return $this->patients()->withTrashed()->exists()
            || $this->appointments()->exists()
            || $this->consultations()->withTrashed()->exists()
            || $this->payments()->withTrashed()->exists()
            || $this->dietPlans()->withTrashed()->exists()
            || $this->clinicMembers()->exists();
    }

    /* ------------------------------------------------------------------
     | Funciones por plan
     * ------------------------------------------------------------------ */

    /**
     * ¿Puede usar la función $feature (p. ej. 'diet_plans')?
     * Solo nutriólogos y según su suscripción más reciente
     * (ver Subscription::grantsFeature). Se calcula en cada petición, así
     * que un cambio de plan o de estado se refleja de inmediato.
     *
     * Subperfil de clínica: no tiene suscripción propia; recibe las
     * funciones del plan config('nutrilife.clinic.member_plan') (Pro)
     * mientras la licencia Consultorio de su clínica siga vigente. Usa la
     * MISMA lista de 'features' que los planes individuales.
     */
    public function hasFeature(string $feature): bool
    {
        if (! $this->isNutritionist()) {
            return false;
        }

        if ($this->isClinicMember()) {
            return (bool) $this->clinic?->clinicLicenseActive()
                && in_array(config('nutrilife.clinic.member_plan'), (array) config('nutrilife.features.'.$feature.'.plans', []), true);
        }

        return (bool) $this->latestSubscription?->grantsFeature($feature);
    }

    /* ------------------------------------------------------------------
     | Consultorios (clínica + subperfiles)
     * ------------------------------------------------------------------ */

    /** Evita repetir la consulta de subperfiles en la misma petición. */
    protected ?bool $hasClinicMembersCache = null;

    /** ¿Es subperfil de nutriólogo de una clínica? */
    public function isClinicMember(): bool
    {
        return $this->clinic_id !== null;
    }

    /**
     * ¿Es la cuenta principal de un consultorio? Su plan más reciente es el
     * de clínica (config nutrilife.clinic.plan) o ya administra subperfiles
     * (aunque el plan haya vencido, sigue siendo la dueña de su equipo).
     */
    public function isClinic(): bool
    {
        if (! $this->isNutritionist() || $this->isClinicMember()) {
            return false;
        }

        if ($this->latestSubscription?->plan?->name === config('nutrilife.clinic.plan')) {
            return true;
        }

        return $this->hasClinicMembersCache ??= $this->clinicMembers()->exists();
    }

    /**
     * ¿La licencia del consultorio está vigente? (plan de clínica pagado y
     * dentro del periodo, cuenta no desactivada). De esto dependen el acceso
     * de sus subperfiles y la posibilidad de crear nuevos.
     */
    public function clinicLicenseActive(): bool
    {
        $subscription = $this->latestSubscription;

        return $this->isClinic()
            && ! $this->isDisabled()
            && $subscription !== null
            && $subscription->plan?->name === config('nutrilife.clinic.plan')
            && $subscription->hasPaidAccess();
    }

    /** Límite de subperfiles por clínica (null = sin límite). */
    public static function clinicMemberLimit(): ?int
    {
        $limit = config('nutrilife.clinic.max_members');

        return $limit === null ? null : (int) $limit;
    }

    public function canAddClinicMember(): bool
    {
        $limit = self::clinicMemberLimit();

        return $this->clinicLicenseActive()
            && ($limit === null || $this->clinicMembers()->count() < $limit);
    }

    /**
     * Ids de los nutriólogos de la organización: la cuenta principal (que
     * también atiende pacientes) y sus subperfiles.
     *
     * @return list<int>
     */
    public function clinicNutritionistIds(): array
    {
        return array_values(array_map('intval', array_merge(
            [$this->id],
            $this->clinicMembers()->pluck('id')->all()
        )));
    }

    /**
     * Cómo se identifica el perfil en la interfaz. Solo la cuenta principal
     * del consultorio es "Clínica"; sus subperfiles y los profesionales
     * individuales siguen siendo "Nutriólogo".
     */
    public function profileTypeLabel(): string
    {
        return match (true) {
            $this->isAdmin() => 'Administrador',
            $this->isClinic() => 'Clínica',
            default => 'Nutriólogo',
        };
    }

    /* ------------------------------------------------------------------
     | Helpers de rol
     * ------------------------------------------------------------------ */

    public function isAdmin(): bool
    {
        return (int) $this->role_id === Role::ADMIN;
    }

    public function isNutritionist(): bool
    {
        return (int) $this->role_id === Role::NUTRITIONIST;
    }

    /* ------------------------------------------------------------------
     | Estado de la cuenta y verificación profesional (son cosas distintas)
     * ------------------------------------------------------------------ */

    /** Cuenta registrada que todavía no se habilita (ver AccountActivation). */
    public function isPendingActivation(): bool
    {
        return $this->account_status === self::ACCOUNT_PENDING;
    }

    /**
     * Desactivada por el administrador o por su clínica. Un subperfil
     * también queda fuera si su clínica fue desactivada.
     */
    public function isDisabled(): bool
    {
        return $this->account_status === self::ACCOUNT_DISABLED
            || ($this->isClinicMember() && $this->clinic?->account_status === self::ACCOUNT_DISABLED);
    }

    public function accountStatusLabel(): string
    {
        return self::ACCOUNT_STATUS_LABELS[$this->account_status] ?? ucfirst((string) $this->account_status);
    }

    /**
     * Estado del correo para mostrarlo (Mi perfil, administrador).
     *  - verified   → users.email_verified_at tiene fecha (verificación real).
     *  - simulated  → no verificado, pero el entorno de pruebas lo muestra
     *                 como verificado (config nutrilife.simulated_email_verification).
     *                 Nunca en producción y nunca escribe email_verified_at.
     *  - unverified → sin verificar.
     *
     * @return array{state: string, label: string}
     */
    public function emailVerificationState(): array
    {
        if ($this->email_verified_at !== null) {
            return ['state' => 'verified', 'label' => 'Verificado'];
        }

        if (config('nutrilife.simulated_email_verification') && ! app()->environment('production')) {
            return ['state' => 'simulated', 'label' => 'Verificado (simulado · pruebas)'];
        }

        return ['state' => 'unverified', 'label' => 'Sin verificar'];
    }

    /**
     * ¿Alguien verificó de verdad la cédula/título? Hoy siempre es false
     * salvo que se llene professional_verified_at: el tiempo de espera de
     * la activación NO cuenta como verificación.
     */
    public function isProfessionallyVerified(): bool
    {
        return $this->professional_verified_at !== null;
    }
}
