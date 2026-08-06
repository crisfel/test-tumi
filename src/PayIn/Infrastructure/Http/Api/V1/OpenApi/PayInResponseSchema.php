<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Schema OpenAPI del envelope de respuesta exitosa de un PayIn.
 */
#[OA\Schema(
    schema: 'PayInResponse',
    required: ['data'],
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/PayIn'),
    ],
)]
final class PayInResponseSchema
{
}
