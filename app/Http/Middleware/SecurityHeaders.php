<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad estándar para la web y la API (auditoría
 * 2026-11-12: producción no enviaba ninguna).
 *
 * - X-Frame-Options: nadie puede meter NutriLife en un iframe ajeno
 *   (clickjacking).
 * - X-Content-Type-Options: el navegador no "adivina" tipos de archivo.
 * - Referrer-Policy: no se filtran rutas internas a otros sitios.
 * - Permissions-Policy: la app no usa cámara, micrófono ni ubicación.
 * - Strict-Transport-Security: solo por HTTPS (Laravel Cloud); en local
 *   (http://localhost) no se envía para no romper el desarrollo.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Frame-Options', 'SAMEORIGIN', false);
        $headers->set('X-Content-Type-Options', 'nosniff', false);
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()', false);

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', false);
        }

        return $response;
    }
}
