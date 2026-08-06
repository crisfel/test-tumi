<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Schema OpenAPI de un error individual del envelope homogéneo.
 */
#[OA\Schema(
    schema: 'Error',
    required: ['code', 'message'],
    properties: [
        new OA\Property(property: 'code', description: 'Código de error estable', type: 'string', example: 'REFERENCE_ALREADY_USED'),
        new OA\Property(property: 'message', description: 'Mensaje legible del error', type: 'string', example: 'La referencia "postman-0001" ya fue utilizada en otra operación.'),
        new OA\Property(property: 'meta', description: 'Contexto adicional del error', type: 'object', additionalProperties: true),
    ],
)]
final class ErrorSchema
{
}
