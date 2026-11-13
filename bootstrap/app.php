<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureClinicOwner;
use App\Http\Middleware\EnsureFeature;
use App\Http\Middleware\EnsureNutritionist;
use App\Http\Middleware\EnsurePatientAccess;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\UseBuiltAssetsOutsideLocalhost;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', // app móvil del paciente
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Túnel HTTPS (cloudflared / ngrok) → `php artisan serve` en HTTP.
        // El túnel corre en esta misma máquina y reenvía X-Forwarded-Proto/
        // Host: se confía solo en él (127.0.0.1 / ::1) para que Laravel sepa
        // que la petición original fue HTTPS. Sin esto, los formularios
        // apuntaban a http://… dentro de una página https://… y el login
        // respondía 419 Page Expired. TRUSTED_PROXIES en .env lo amplía.
        // En Laravel Cloud no se fija nada: el framework ya confía en su
        // proxy automáticamente (si se fija aquí, esa detección se anula y
        // la app cree que la petición es HTTP).
        if (! laravel_cloud()) {
            $middleware->trustProxies(
                at: array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '127.0.0.1,::1'))))),
            );
        }

        // Cabeceras de seguridad en todas las respuestas (web y API)
        $middleware->append(SecurityHeaders::class);

        // Funciones por plan: ->middleware('feature:diet_plans')
        // Por el túnel se usan los JS compilados, no el servidor de `npm run dev`.
        $middleware->web(append: [
            UseBuiltAssetsOutsideLocalhost::class,
        ]);

        // Roles: ->middleware('admin' | 'clinic' | 'nutritionist')
        $middleware->alias([
            'feature' => EnsureFeature::class,
            'admin' => EnsureAdmin::class,
            'clinic' => EnsureClinicOwner::class,
            'nutritionist' => EnsureNutritionist::class,
            // App móvil: expediente activo y nutriólogo habilitado
            'patient.access' => EnsurePatientAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
