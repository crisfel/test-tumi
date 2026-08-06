<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Schema OpenAPI del envelope de error homogéneo de la API.
 */
#[OA\Schema(
    schema: 'ErrorResponse',
    required: ['errors'],
    properties: [
        new OA\Property(
            property: 'errors',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Error'),
        ),
    ],
)]
final class ErrorResponseSchema
{
}
