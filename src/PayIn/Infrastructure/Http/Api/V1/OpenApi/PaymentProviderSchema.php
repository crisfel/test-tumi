<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Schema OpenAPI de la representación de un proveedor de pago.
 */
#[OA\Schema(
    schema: 'PaymentProvider',
    required: ['id', 'code', 'name', 'is_active', 'supported_types'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '019fd715-eb24-7683-b8d2-9d83ffdca22d'),
        new OA\Property(property: 'code', type: 'string', example: 'fakepay'),
        new OA\Property(property: 'name', type: 'string', example: 'FakePay'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(
            property: 'supported_types',
            description: 'Matriz de capacidades: tipos de método que el proveedor puede procesar',
            type: 'array',
            items: new OA\Items(type: 'string', enum: ['card', 'bank_transfer', 'wallet', 'pse', 'cash']),
            example: ['card'],
        ),
    ],
)]
final class PaymentProviderSchema
{
}
