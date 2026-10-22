<?php

namespace App\Models;

use App\Models\Concerns\HasInternalReference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pago de un paciente. internal_reference (referencia interna de NutriLife)
 * la genera HasInternalReference; `reference` es la referencia externa
 * opcional (clave de rastreo, folio de la terminal…).
 */
class Payment extends Model
{
    use HasInternalReference;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'patient_id',
        'appointment_id',
        'amount',
        'paid_at',
        'concept',
        'payment_method',
        'status',
        'reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'patient_id' => 'integer',
            'appointment_id' => 'integer',
            'paid_at' => 'datetime',
            'amount' => 'decimal:2',
            'status_changed_at' => 'datetime',
        ];
    }

    /** payments.status (VARCHAR, por defecto 'Pagado'). */
    public const STATUS_PAID = 'Pagado';

    public const STATUS_PENDING = 'Pendiente';

    public const STATUS_CANCELLED = 'Cancelado';

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Quién cambió el estado (status_changed_by / status_changed_at). No es
     * asignable en masa: lo llena PaymentController con forceFill.
     */
    public function statusChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * patients usa borrado lógico; el historial (citas, consultas, pagos,
     * evaluaciones) debe seguir mostrando al paciente aunque se elimine,
     * por eso la relación incluye pacientes eliminados.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}