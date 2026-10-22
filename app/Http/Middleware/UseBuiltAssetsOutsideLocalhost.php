<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * JS compilado para visitas que no vienen de esta PC (túnel cloudflared).
 *
 * Con `npm run dev`, public/hot apunta a http://[::1]:5173. Quien entra por
 * el túnel no puede llegar a ese localhost, y una página https bloquearía un
 * script http: no cargaba ningún JS (botón de tema, confirmaciones,
 * calendario, gráficas). Fuera de localhost se usan siempre los archivos
 * compilados de public/build (`npm run build`); en esta PC se conserva la
 * recarga en vivo de Vite.
 */
class UseBuiltAssetsOutsideLocalhost
{
    public const LOCAL_HOSTS = ['localhost', '127.0.0.1', '::1', '[::1]'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->getHost(), self::LOCAL_HOSTS, true)) {
            // Ruta que nunca existe: Laravel cae al manifest de public/build.
            Vite::useHotFile(storage_path('framework/vite.hot.disabled'));
        }

        return $next($request);
    }
}
