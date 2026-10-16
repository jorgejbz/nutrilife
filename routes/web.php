<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LandingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\EnsureTrialSubscription;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\DietPlanController;
use App\Http\Controllers\DietPlanMealController;
use App\Http\Controllers\DietPlanItemController;

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
// EnsureTrialSubscription: crea/actualiza la prueba gratuita del nutriólogo
Route::middleware(['auth', EnsureTrialSubscription::class])->group(function () {

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

/*
    |----------------------------------------------------------------
    | Planes alimenticios
    |----------------------------------------------------------------
    */

            Route::get('/planes-alimenticios', [DietPlanController::class, 'index'])->name('diet-plans.index');
            Route::get('/planes-alimenticios/crear', [DietPlanController::class, 'create'])->name('diet-plans.create');
            Route::post('/planes-alimenticios',[DietPlanController::class, 'store'])->name('diet-plans.store');
            Route::get('/planes-alimenticios/{plan}',[DietPlanController::class, 'show'])->name('diet-plans.show');
            Route::post('/planes-alimenticios/{plan}/comidas',[DietPlanMealController::class, 'store'])->name('diet-plans.meals.store');
            Route::delete('/planes-alimenticios/{plan}/comidas/{meal}',[DietPlanMealController::class, 'destroy'])->name('diet-plans.meals.destroy');
            Route::post('/planes-alimenticios/{plan}/comidas/{meal}/alimentos',[DietPlanItemController::class, 'store'])->name('diet-plans.items.store');
            Route::delete('/planes-alimenticios/{plan}/comidas/{meal}/alimentos/{item}',[DietPlanItemController::class, 'destroy'])->name('diet-plans.items.destroy');
            Route::post('/planes-alimenticios/{plan}/comidas/{meal}/alimentos-personalizados',[DietPlanItemController::class, 'storeCustom'])->name('diet-plans.items.custom.store');
            Route::delete('/diet-plans/{diet_plan}', [DietPlanController::class,'destroy'])->name('diet-plans.destroy');
            Route::post('diet-plans/{plan}/meals/{meal}/dishes', [DietPlanMealController::class, 'storeDish'])->name('diet-plans.meals.dishes.store');
            require __DIR__.'/auth.php';
