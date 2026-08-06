<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Schema OpenAPI de la representación de una cuenta.
 */
#[OA\Schema(
    schema: 'Account',
    required: ['id', 'client_id', 'currency', 'balance'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '019fd715-ec1a-7a7e-ab6f-f497aa52abe4'),
        new OA\Property(property: 'client_id', type: 'string', format: 'uuid', example: '019fd715-ebf8-7223-ada8-b3c168a28e22'),
        new OA\Property(property: 'currency', type: 'string', enum: ['COP', 'USD', 'EUR', 'MXN'], example: 'COP'),
        new OA\Property(property: 'balance', description: 'Saldo en unidades menores (enteras)', type: 'integer', example: 0),
    ],
)]
final class AccountSchema
{
}
