<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Schema OpenAPI de un movimiento del libro mayor (extracto).
 */
#[OA\Schema(
    schema: 'AccountMovement',
    required: ['id', 'account_id', 'type', 'amount', 'currency', 'balance_after', 'occurred_at'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'account_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'type', description: 'credit: aumenta el saldo · debit: disminuye el saldo', type: 'string', enum: ['credit', 'debit'], example: 'debit'),
        new OA\Property(property: 'amount', description: 'Monto del movimiento en unidades menores', type: 'integer', example: 2000),
        new OA\Property(property: 'currency', type: 'string', enum: ['COP', 'USD', 'EUR', 'MXN'], example: 'COP'),
        new OA\Property(property: 'balance_after', description: 'Saldo de la cuenta después del movimiento', type: 'integer', example: 8000),
        new OA\Property(property: 'pay_in_id', description: 'PayIn relacionado (null en ajustes manuales y apertura)', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time'),
    ],
)]
final class AccountMovementSchema
{
}
