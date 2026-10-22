<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
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
        $middleware->trustProxies(
            at: array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '127.0.0.1,::1'))))),
        );

        // Funciones por plan: ->middleware('feature:diet_plans')
        // Por el túnel se usan los JS compilados, no el servidor de `npm run dev`.
        $middleware->web(append: [
            \App\Http\Middleware\UseBuiltAssetsOutsideLocalhost::class,
        ]);

        // Roles: ->middleware('admin' | 'clinic' | 'nutritionist')
        $middleware->alias([
            'feature' => \App\Http\Middleware\EnsureFeature::class,
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'clinic' => \App\Http\Middleware\EnsureClinicOwner::class,
            'nutritionist' => \App\Http\Middleware\EnsureNutritionist::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
