<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evaluation extends Model
{
    protected $fillable = [
        'patient_id',
        'evaluation_date',
        'weight',
        'height',
        'bmi',
        'body_fat',
        'muscle_mass',
        'waist',
        'hip',
        'chest',
        'arm',
        'thigh',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'patient_id' => 'integer',
            'evaluation_date' => 'date',

            'weight' => 'float',
            'height' => 'float',
            'bmi' => 'float',

            'body_fat' => 'float',
            'muscle_mass' => 'float',

            'waist' => 'float',
            'hip' => 'float',
            'chest' => 'float',
            'arm' => 'float',
            'thigh' => 'float',
        ];
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
}