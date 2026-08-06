<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Schema OpenAPI del cuerpo de la petición POST /api/v1/clients.
 */
#[OA\Schema(
    schema: 'StoreClientRequest',
    required: ['name', 'email'],
    properties: [
        new OA\Property(property: 'name', description: 'Nombre del cliente', type: 'string', maxLength: 100, example: 'Carlos Rodríguez'),
        new OA\Property(property: 'email', description: 'Email único del cliente', type: 'string', format: 'email', example: 'carlos.rodriguez@example.com'),
    ],
)]
final class StoreClientRequestSchema
{
}
