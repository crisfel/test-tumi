<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Schema OpenAPI del envelope de respuesta de una cuenta.
 */
#[OA\Schema(
    schema: 'AccountResponse',
    required: ['data'],
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/Account'),
    ],
)]
final class AccountResponseSchema
{
}
