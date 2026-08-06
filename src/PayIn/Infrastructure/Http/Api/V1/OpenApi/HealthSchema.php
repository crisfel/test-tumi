<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Schema OpenAPI del endpoint de salud de la plataforma.
 */
#[OA\Schema(
    schema: 'HealthResponse',
    properties: [
        new OA\Property(property: 'status', description: 'Estado de la aplicación', type: 'string', example: 'ok'),
    ],
)]
final class HealthSchema
{
}
