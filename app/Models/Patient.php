<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Paciente (tabla `patients`).
 *
 * En la nueva BD `email` es obligatorio y único por nutriólogo
 * (índice patients_user_id_email_unique): junto con el access_code del
 * nutriólogo es la credencial del paciente para la app móvil.
 */
class Patient extends Model
{
    use SoftDeletes;

    /** Valores de patient_allergens.severity (ENUM). */
    public const ALLERGY_SEVERITIES = ['leve', 'moderada', 'severa'];

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'birth_date',
        'sex',
        'phone',
        'email',
        'occupation',
        'goal',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'birth_date' => 'date',
            'active' => 'boolean',
        ];
    }

    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
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

    public function pathologies(): BelongsToMany
    {
        return $this->belongsToMany(Pathology::class, 'patient_pathologies')
            ->withPivot(['diagnosed_at', 'notes'])
            ->withTimestamps();
    }

    public function allergens(): BelongsToMany
    {
        return $this->belongsToMany(Allergen::class, 'patient_allergens')
            ->withPivot('severity')
            ->withTimestamps();
    }

    
}
