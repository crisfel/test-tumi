<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Schema OpenAPI de la página de listado de métodos de pago.
 */
#[OA\Schema(
    schema: 'PaymentMethodPage',
    required: ['data', 'meta'],
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PaymentMethod'),
        ),
        new OA\Property(
            property: 'meta',
            required: ['total', 'limit', 'offset'],
            properties: [
                new OA\Property(property: 'total', type: 'integer', example: 2),
                new OA\Property(property: 'limit', type: 'integer', example: 20),
                new OA\Property(property: 'offset', type: 'integer', example: 0),
            ],
        ),
    ],
)]
final class PaymentMethodPageSchema
{
}
