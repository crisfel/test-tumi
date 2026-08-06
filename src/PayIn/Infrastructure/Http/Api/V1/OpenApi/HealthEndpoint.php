<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Documentación OpenAPI del health check de la plataforma.
 */
#[OA\Get(
    path: '/up',
    summary: 'Health check',
    description: 'Verifica que la aplicación está en funcionamiento.',
    tags: ['System'],
    responses: [
        new OA\Response(response: 200, description: 'Aplicación operativa', content: new OA\JsonContent(ref: '#/components/schemas/HealthResponse')),
    ],
)]
final class HealthEndpoint
{
}
