<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Schema OpenAPI del ajuste de saldo PATCH /api/v1/accounts/{id}/balance.
 */
#[OA\Schema(
    schema: 'AdjustAccountBalanceRequest',
    required: ['amount', 'direction', 'currency'],
    properties: [
        new OA\Property(property: 'amount', description: 'Monto del ajuste en unidades menores', type: 'integer', minimum: 1, example: 5000),
        new OA\Property(property: 'direction', description: '"increase" AUMENTA el saldo (crédito); "decrease" DISMINUYE el saldo (débito, requiere fondos)', type: 'string', enum: ['increase', 'decrease'], example: 'increase'),
        new OA\Property(property: 'currency', description: 'Moneda del ajuste', type: 'string', enum: ['COP', 'USD', 'EUR', 'MXN'], example: 'COP'),
    ],
)]
final class AdjustAccountBalanceRequestSchema
{
}
