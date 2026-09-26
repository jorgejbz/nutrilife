<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Roles de la plataforma (tabla `roles`). La tabla no tiene timestamps.
 * Los ids 1 y 2 los inserta la migración / proyecto_v3.sql.
 */
class Role extends Model
{
    public const NUTRITIONIST = 1;
    public const ADMIN = 2;

    public $timestamps = false;

    protected $fillable = ['name', 'description'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
