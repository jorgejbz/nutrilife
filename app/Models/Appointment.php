<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

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

    /**
     * ¿El nutriólogo ya tiene otra cita abierta que se empalma con este
     * horario? Cada cita dura config('appointments.slot_minutes') (60 min);
     * las canceladas no ocupan lugar. Es la misma regla que usa la app móvil
     * para mostrar horas libres (App\Services\AppointmentAvailability).
     */
    public static function overlapsFor(int $userId, \DateTimeInterface $at, ?int $ignoreId = null): bool
    {
        $slot = (int) config('appointments.slot_minutes', 60);
        $start = Carbon::instance($at);

        return static::where('user_id', $userId)
            ->where('status', '!=', 'Cancelada')
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->where('appointment_at', '>', $start->copy()->subMinutes($slot))
            ->where('appointment_at', '<', $start->copy()->addMinutes($slot))
            ->exists();
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
