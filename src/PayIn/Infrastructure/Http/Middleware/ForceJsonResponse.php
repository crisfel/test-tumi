<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fuerza respuestas JSON en los endpoints de la API.
 *
 * La API es únicamente JSON; cualquier petición no compatible recibe el
 * mismo envelope de error que el resto de la plataforma.
 */
final class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
