<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LandingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\ConsultationController;

/*
|--------------------------------------------------------------------------
| Landing (pública)
|--------------------------------------------------------------------------
*/
Route::get('/', [LandingController::class, 'index'])
    ->name('landing');

/*
|--------------------------------------------------------------------------
| Rutas autenticadas
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    /*
    |----------------------------------------------------------------
    | Dashboard
    |----------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard.index');

    /*
    |----------------------------------------------------------------
    | Pacientes
    |----------------------------------------------------------------
    */
    Route::resource('pacientes', PatientController::class);

    // Evaluaciones (anidadas a paciente)
    Route::prefix('pacientes/{paciente}/evaluaciones')
        ->name('evaluaciones.')
        ->group(function () {
            Route::get('/create', [EvaluationController::class, 'create'])->name('create');
            Route::post('/', [EvaluationController::class, 'store'])->name('store');
        });

    /*
    |----------------------------------------------------------------
    | Citas
    |----------------------------------------------------------------
    */
    // show/edit/update no están implementados en el controlador (devolvían
    // una página en blanco), por eso se registran solo los métodos reales.
    Route::resource('citas', AppointmentController::class)
        ->only(['index', 'create', 'store', 'destroy']);

    Route::patch('/citas/{cita}/cancelar', [AppointmentController::class, 'cancel'])
        ->name('citas.cancel');

    /*
    |----------------------------------------------------------------
    | Consultas
    |----------------------------------------------------------------
    */
    Route::resource('consultas', ConsultationController::class)
        ->only(['index', 'create', 'store']);

    /*
    |----------------------------------------------------------------
    | Pagos
    |----------------------------------------------------------------
    */
    Route::resource('pagos', PaymentController::class)
        ->only(['index', 'create', 'store']);

    /*
    |----------------------------------------------------------------
    | Progreso
    |----------------------------------------------------------------
    */
    Route::prefix('progreso')
        ->name('progreso.')
        ->group(function () {
            Route::get('/', [ProgressController::class, 'index'])->name('index');
            Route::get('/{paciente}', [ProgressController::class, 'show'])->name('show');
        });

    /*
    |----------------------------------------------------------------
    | Perfil
    |----------------------------------------------------------------
    */
    Route::prefix('profile')
        ->name('profile.')
        ->group(function () {
            Route::get('/', [ProfileController::class, 'edit'])->name('edit');
            Route::patch('/', [ProfileController::class, 'update'])->name('update');
            Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
        });
});

require __DIR__.'/auth.php';
