<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Schema OpenAPI de la página del catálogo de proveedores.
 */
#[OA\Schema(
    schema: 'PaymentProviderPage',
    required: ['data', 'meta'],
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PaymentProvider'),
        ),
        new OA\Property(
            property: 'meta',
            required: ['total', 'limit', 'offset'],
            properties: [
                new OA\Property(property: 'total', type: 'integer', example: 3),
                new OA\Property(property: 'limit', type: 'integer', example: 20),
                new OA\Property(property: 'offset', type: 'integer', example: 0),
            ],
        ),
    ],
)]
final class PaymentProviderPageSchema
{
}
