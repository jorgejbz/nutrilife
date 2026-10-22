<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Secciones del consultorio (/clinica/...). Uso: ->middleware('clinic').
 *
 * Solo la cuenta principal de una clínica (User::isClinic) entra. Los
 * subperfiles, los nutriólogos individuales y el administrador reciben 403.
 * Cada controlador además filtra por la clínica autenticada, así que una
 * clínica nunca ve ni modifica datos de otra.
 */
class EnsureClinicOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isClinic(), Response::HTTP_FORBIDDEN, 'Esta sección es solo para la cuenta principal de un consultorio.');

        return $next($request);
    }
}
