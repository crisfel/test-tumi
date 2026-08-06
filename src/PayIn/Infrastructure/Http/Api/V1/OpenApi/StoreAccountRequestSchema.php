<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Schema OpenAPI del cuerpo de la petición POST /api/v1/accounts.
 */
#[OA\Schema(
    schema: 'StoreAccountRequest',
    required: ['client_id', 'currency'],
    properties: [
        new OA\Property(property: 'client_id', description: 'UUID del cliente titular de la cuenta', type: 'string', format: 'uuid', example: '019fd715-ebf8-7223-ada8-b3c168a28e22'),
        new OA\Property(property: 'currency', description: 'Moneda de la cuenta (una por cliente y moneda)', type: 'string', enum: ['COP', 'USD', 'EUR', 'MXN'], example: 'COP'),
    ],
)]
final class StoreAccountRequestSchema
{
}
