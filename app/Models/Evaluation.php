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

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}