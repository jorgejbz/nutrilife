<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Cita (tabla `appointments`). Sin cambios de columnas en la nueva BD. */
class Appointment extends Model
{
    protected $fillable = [
        'user_id',
        'patient_id',
        'appointment_at',
        'reason',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'patient_id' => 'integer',
            'appointment_at' => 'datetime',
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

    public function consultation(): HasOne
    {
        return $this->hasOne(Consultation::class);
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
