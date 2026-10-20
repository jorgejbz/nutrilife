<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LandingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureTrialSubscription;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\DietPlanController;
use App\Http\Controllers\FoodCatalogController;
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
// EnsureAccountIsActive: cuentas pendientes de activación no entran.
// EnsureTrialSubscription: crea/actualiza la prueba gratuita y, si ya no hay
// prueba ni paquete vigente, manda a la pantalla de contratación.
// PreventBackHistory: las páginas privadas no se guardan en caché del
// navegador (después de cerrar sesión, "Atrás" no muestra datos).
Route::middleware(['auth', EnsureAccountIsActive::class, EnsureTrialSubscription::class, PreventBackHistory::class])->group(function () {

    /*
    |----------------------------------------------------------------
    | Contratación de paquetes (siempre accesible, ver
    | EnsureTrialSubscription::ALWAYS_ALLOWED)
    |----------------------------------------------------------------
    */
    Route::get('/contratar', [SubscriptionController::class, 'index'])
        ->name('subscription.plans');
    Route::post('/contratar/{subscriptionPlan}', [SubscriptionController::class, 'checkout'])
        ->name('subscription.checkout');

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

    /*
    |----------------------------------------------------------------
    | Catálogo de alimentos (solo consulta; mismo acceso que los planes)
    |----------------------------------------------------------------
    | Tabla `foods` global. Se usa al armar los planes; aquí se consulta
    | con búsqueda, filtros y detalle de cada alimento.
    */
    Route::get('/alimentos', [FoodCatalogController::class, 'index'])
        ->middleware('feature:diet_plans')
        ->name('foods.index');

    /*
    |----------------------------------------------------------------
    | Planes alimenticios (solo Pro, Consultorio y prueba gratuita)
    |----------------------------------------------------------------
    | feature:diet_plans → App\Http\Middleware\EnsureFeature: bloquea la
    | sección completa si el plan no la incluye (aunque se escriba la URL).
    | La propiedad de cada plan la revisa App\Policies\DietPlanPolicy.
    | {plan} solo se resuelve entre los planes del nutriólogo (Route::bind en
    | AppServiceProvider → 404 para planes ajenos) y scopeBindings() exige
    | que {meal} pertenezca a {plan} e {item} a {meal}.
    */
    Route::prefix('planes-alimenticios')
        ->name('diet-plans.')
        ->middleware('feature:diet_plans')
        ->scopeBindings()
        ->group(function () {
            Route::get('/', [DietPlanController::class, 'index'])->name('index');
            Route::get('/crear', [DietPlanController::class, 'create'])->name('create');
            Route::post('/', [DietPlanController::class, 'store'])->name('store');
            Route::get('/{plan}', [DietPlanController::class, 'show'])->name('show');
            Route::get('/{plan}/editar', [DietPlanController::class, 'edit'])->name('edit');
            Route::put('/{plan}', [DietPlanController::class, 'update'])->name('update');
            Route::delete('/{plan}', [DietPlanController::class, 'destroy'])->name('destroy');
            Route::post('/{plan}/duplicar', [DietPlanController::class, 'duplicate'])->name('duplicate');

            // Tiempos de comida
            Route::post('/{plan}/comidas', [DietPlanMealController::class, 'store'])->name('meals.store');
            Route::patch('/{plan}/comidas/{meal}', [DietPlanMealController::class, 'update'])->name('meals.update');
            Route::delete('/{plan}/comidas/{meal}', [DietPlanMealController::class, 'destroy'])->name('meals.destroy');

            // Alimentos del catálogo dentro de una comida
            Route::post('/{plan}/comidas/{meal}/alimentos', [DietPlanItemController::class, 'store'])->name('items.store');
            Route::patch('/{plan}/comidas/{meal}/alimentos/{item}', [DietPlanItemController::class, 'update'])->name('items.update');
            Route::delete('/{plan}/comidas/{meal}/alimentos/{item}', [DietPlanItemController::class, 'destroy'])->name('items.destroy');
        });
});

require __DIR__.'/auth.php';
