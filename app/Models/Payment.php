<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
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
        ];
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