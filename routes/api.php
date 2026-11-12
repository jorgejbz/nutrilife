<?php

/*
|--------------------------------------------------------------------------
| API de la app móvil del paciente (prefijo /api)
|--------------------------------------------------------------------------
| Login con access_code del nutriólogo + correo del paciente → token Bearer
| (Sanctum). Todas las demás rutas piden: Authorization: Bearer <token>
*/

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DietPlanController;
use App\Http\Controllers\Api\InfoController;
use App\Http\Controllers\Api\RestrictionController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware(['auth:sanctum', 'patient.access', 'throttle:120,1'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/diet', [DietPlanController::class, 'show']);
    Route::get('/restrictions', [RestrictionController::class, 'index']);

    Route::get('/consultations', [InfoController::class, 'consultations']);
    Route::get('/evaluations', [InfoController::class, 'evaluations']);
    Route::get('/allergies', [InfoController::class, 'allergies']);
    Route::get('/pathologies', [InfoController::class, 'pathologies']);

    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::get('/availability', [AppointmentController::class, 'availability']);
    Route::post('/appointments/{id}/cancel', [AppointmentController::class, 'cancel'])->whereNumber('id');
    Route::post('/appointments/{id}/reschedule', [AppointmentController::class, 'reschedule'])->whereNumber('id');
});
