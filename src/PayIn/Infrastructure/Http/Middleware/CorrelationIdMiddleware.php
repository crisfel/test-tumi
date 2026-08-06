<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PayIn\Shared\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

/**
 * Correlaciona una petición HTTP con todos sus registros de log.
 *
 * Lee (o genera) el encabezado X-Correlation-Id y lo inyecta en el contexto
 * de logging de Laravel para que la traza del PayIn sea rastreable de
 * punta a punta (request → orquestador → proveedor).
 */
final class CorrelationIdMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('X-Correlation-Id');
        $correlationId = is_string($header) && $header !== '' ? $header : Uuid::v7()->toString();

        Log::withContext(['correlation_id' => $correlationId]);

        $response = $next($request);
        $response->headers->set('X-Correlation-Id', $correlationId);

        return $response;
    }
}
