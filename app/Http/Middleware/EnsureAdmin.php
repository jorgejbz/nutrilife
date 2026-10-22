<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Panel del administrador global (/admin/...). Uso: ->middleware('admin').
 *
 * La revisión es en el servidor y en cada petición: escribir la URL a mano o
 * mandar la petición directamente responde 403 a cualquier cuenta que no
 * tenga role_id = Role::ADMIN. role_id no es asignable en masa (User) y el
 * registro público siempre crea nutriólogos, así que nadie puede volverse
 * administrador desde un formulario; el primero se crea con
 * `php artisan nutrilife:create-admin`.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isAdmin(), Response::HTTP_FORBIDDEN, 'Esta sección es solo para el administrador de NutriLife.');

        return $next($request);
    }
}
