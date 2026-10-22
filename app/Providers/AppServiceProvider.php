<?php

namespace App\Providers;

use App\Models\DietPlan;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        // Funciones por plan (config/nutrilife.php → features).
        // 'diet-plans' lo usan el menú de AdminLTE ('can'), @can en las
        // vistas y el middleware EnsureFeature. La propiedad de cada plan la
        // revisa App\Policies\DietPlanPolicy (se descubre sola por nombre).
        Gate::define('diet-plans', fn (User $user): bool => $user->hasFeature('diet_plans'));

        // Roles para el menú de AdminLTE ('can') y @can en las vistas. La
        // barrera real son los middleware admin / clinic / nutritionist.
        Gate::define('admin', fn (User $user): bool => $user->isAdmin());
        Gate::define('clinic', fn (User $user): bool => $user->isClinic());
        Gate::define('nutritionist', fn (User $user): bool => $user->isNutritionist());

        // {plan} en /planes-alimenticios/... se busca solo entre los planes
        // del nutriólogo autenticado: el de otra cuenta responde 404 y ni
        // siquiera revela que existe. (Aquí y no en routes/web.php para que
        // funcione también con `php artisan route:cache`.)
        Route::bind('plan', fn (string $value) => DietPlan::ownedBy((int) Auth::id())
            ->whereKey((int) $value)
            ->firstOrFail());
    }
}
