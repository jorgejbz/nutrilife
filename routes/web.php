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
use App\Http\Controllers\DishCatalogController;
use App\Http\Controllers\DietPlanMealController;
use App\Http\Controllers\DietPlanItemController;
use App\Http\Controllers\Clinic\ClinicDashboardController;
use App\Http\Controllers\Clinic\ClinicTeamController;
use App\Http\Controllers\Clinic\ClinicPatientController;
use App\Http\Controllers\Clinic\ClinicAgendaController;
use App\Http\Controllers\Clinic\ClinicConsultationController;
use App\Http\Controllers\Clinic\ClinicPaymentController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminAccountController;
use App\Http\Controllers\Admin\AdminSubscriptionPaymentController;
use App\Http\Controllers\Admin\AdminAuditLogController;

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
    | Perfil (todas las cuentas: nutriólogo, clínica, subperfil, admin)
    |----------------------------------------------------------------
    | Ruta principal en español: /perfil (route('perfil')). /profile se
    | conserva solo como redirección permanente para enlaces antiguos.
    */
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('perfil');
    Route::patch('/perfil', [ProfileController::class, 'update'])->name('perfil.update');
    Route::delete('/perfil', [ProfileController::class, 'destroy'])->name('perfil.destroy');
    Route::permanentRedirect('/profile', '/perfil');

    /*
    |----------------------------------------------------------------
    | Administrador global (role_id = Role::ADMIN)
    |----------------------------------------------------------------
    | Middleware 'admin' (EnsureAdmin): 403 para cualquier otra cuenta,
    | aunque escriba la URL o mande la petición a mano.
    */
    Route::prefix('admin')
        ->name('admin.')
        ->middleware('admin')
        ->group(function () {
            Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
            Route::get('/cuentas', [AdminAccountController::class, 'index'])->name('accounts.index');
            Route::get('/cuentas/{account}', [AdminAccountController::class, 'show'])->name('accounts.show');
            Route::patch('/cuentas/{account}/estado', [AdminAccountController::class, 'updateStatus'])->name('accounts.status');
            Route::post('/cuentas/{account}/plan', [AdminAccountController::class, 'assignPlan'])->name('accounts.plan');
            Route::get('/cobros', [AdminSubscriptionPaymentController::class, 'index'])->name('payments.index');
            Route::patch('/cobros/{subscriptionPayment}/completar', [AdminSubscriptionPaymentController::class, 'complete'])->name('payments.complete');
            Route::get('/auditoria', [AdminAuditLogController::class, 'index'])->name('audit.index');
        });

    /*
    |----------------------------------------------------------------
    | Herramientas de consulta: solo cuentas de nutriólogo
    |----------------------------------------------------------------
    | 'nutritionist' (EnsureNutritionist) manda al administrador a su panel.
    | Clínicas, subperfiles y nutriólogos individuales usan los MISMOS
    | módulos; cada uno ve solo sus propios pacientes (patients.user_id).
    */
    Route::middleware('nutritionist')->group(function () {

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

    Route::patch('/citas/{cita}/confirmar', [AppointmentController::class, 'confirm'])
        ->name('citas.confirm');

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
    // Pagos de pacientes (manuales: efectivo, transferencia, tarjeta…; no
    // hay pasarela). Solo el nutriólogo que registró el pago lo edita o
    // cambia su estado (PaymentController::authorizeOwner).
    Route::resource('pagos', PaymentController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::patch('/pagos/{pago}/completar', [PaymentController::class, 'complete'])
        ->name('pagos.complete');
    Route::patch('/pagos/{pago}/cancelar', [PaymentController::class, 'cancel'])
        ->name('pagos.cancel');

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
    | Consultorio (cuenta principal de la clínica)
    |----------------------------------------------------------------
    | 'clinic' (EnsureClinicOwner): solo User::isClinic(). Cada consulta
    | se limita a la clínica autenticada y sus subperfiles; el
    | subperfil de otra clínica responde 404.
    */
    Route::prefix('clinica')
        ->name('clinic.')
        ->middleware('clinic')
        ->group(function () {
            Route::get('/', [ClinicDashboardController::class, 'index'])->name('dashboard');
            Route::get('/equipo', [ClinicTeamController::class, 'index'])->name('team.index');
            Route::get('/equipo/nuevo', [ClinicTeamController::class, 'create'])->name('team.create');
            Route::post('/equipo', [ClinicTeamController::class, 'store'])->name('team.store');
            Route::get('/equipo/{member}', [ClinicTeamController::class, 'show'])->name('team.show');
            Route::get('/equipo/{member}/editar', [ClinicTeamController::class, 'edit'])->name('team.edit');
            Route::put('/equipo/{member}', [ClinicTeamController::class, 'update'])->name('team.update');
            Route::patch('/equipo/{member}/estado', [ClinicTeamController::class, 'updateStatus'])->name('team.status');
            Route::get('/pacientes', [ClinicPatientController::class, 'index'])->name('patients.index');
            Route::get('/agenda', [ClinicAgendaController::class, 'index'])->name('agenda.index');
            Route::get('/consultas', [ClinicConsultationController::class, 'index'])->name('consultations.index');
            Route::get('/pagos', [ClinicPaymentController::class, 'index'])->name('payments.index');
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
    | Catálogo de platillos (solo consulta; mismo acceso que los planes)
    |----------------------------------------------------------------
    | diet_plan_dishes: platillos del sistema + los propios del
    | nutriólogo, con sus ingredientes y aporte calculado.
    */
    Route::get('/platillos', [DishCatalogController::class, 'index'])
        ->middleware('feature:diet_plans')
        ->name('dishes.index');

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

            // Alimento personalizado (fuera del catálogo) y platillos (actualización 3.2)
            Route::post('/{plan}/comidas/{meal}/alimentos-personalizados', [DietPlanItemController::class, 'storeCustom'])->name('items.custom.store');
            Route::post('/{plan}/comidas/{meal}/platillos', [DietPlanItemController::class, 'storeDish'])->name('meals.dishes.store');
        });
    }); // fin middleware('nutritionist')
});

require __DIR__.'/auth.php';
