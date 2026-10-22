<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bitácora de acciones sensibles (tabla `audit_logs`).
 *
 * La escriben el administrador global (estado y plan de cuentas, cobros de
 * la plataforma), las clínicas (alta y cambios de sus subperfiles) y los
 * cambios de estado de pagos. Solo se agregan filas: nunca se editan.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'action', 'subject_type', 'subject_id', 'details', 'ip_address'];

    /** Textos para mostrar cada acción. */
    public const ACTION_LABELS = [
        'account.activated' => 'Cuenta activada',
        'account.deactivated' => 'Cuenta desactivada',
        'account.reactivated' => 'Cuenta reactivada',
        'account.plan_assigned' => 'Plan asignado',
        'clinic.member_created' => 'Subperfil creado',
        'clinic.member_updated' => 'Subperfil actualizado',
        'clinic.member_deactivated' => 'Subperfil desactivado',
        'clinic.member_reactivated' => 'Subperfil reactivado',
        'patient.reassigned' => 'Paciente asignado a otro nutriólogo',
        'payment.created' => 'Pago registrado',
        'payment.completed' => 'Pago marcado como completado',
        'payment.cancelled' => 'Pago cancelado',
        'payment.updated' => 'Pago editado',
        'subscription_payment.completed' => 'Cobro de la plataforma confirmado',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(?User $actor, string $action, ?Model $subject = null, array $details = []): self
    {
        return static::create([
            'user_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'details' => $details ?: null,
            'ip_address' => request()?->ip(),
        ]);
    }

    public function actionLabel(): string
    {
        return self::ACTION_LABELS[$this->action] ?? $this->action;
    }
}
