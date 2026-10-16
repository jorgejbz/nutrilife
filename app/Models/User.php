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
#[Fillable(['name', 'email', 'password', 'role_id', 'access_code'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
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

    /**
     * ¿Tiene información clínica registrada? patients, appointments,
     * consultations, payments y diet_plans referencian a users SIN borrado
     * en cascada, así que una cuenta con datos no se puede eliminar sin
     * perder expedientes (y MySQL lo rechazaría).
     */
    public function hasClinicalData(): bool
    {
        return $this->patients()->withTrashed()->exists()
            || $this->appointments()->exists()
            || $this->consultations()->withTrashed()->exists()
            || $this->payments()->withTrashed()->exists()
            || $this->dietPlans()->withTrashed()->exists();
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
}
