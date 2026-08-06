<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Schema OpenAPI de la representación de un cliente.
 */
#[OA\Schema(
    schema: 'Client',
    required: ['id', 'name', 'email'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '019fd715-ebf8-7223-ada8-b3c168a28e22'),
        new OA\Property(property: 'name', type: 'string', example: 'Carlos Rodríguez'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'carlos.rodriguez@example.com'),
    ],
)]
final class ClientSchema
{
}
