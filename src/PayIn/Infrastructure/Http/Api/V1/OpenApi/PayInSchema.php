<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Schema OpenAPI de la representación de un PayIn.
 */
#[OA\Schema(
    schema: 'PayIn',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'client_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'account_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'payment_method_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'amount', type: 'integer'),
        new OA\Property(property: 'currency', type: 'string', enum: ['COP', 'USD', 'EUR', 'MXN']),
        new OA\Property(property: 'status', type: 'string', enum: ['created', 'validated', 'processing', 'processed', 'failed']),
        new OA\Property(property: 'reference', type: 'string', nullable: true),
        new OA\Property(property: 'provider_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'provider_transaction_id', type: 'string', nullable: true),
        new OA\Property(property: 'error_code', type: 'string', nullable: true),
        new OA\Property(property: 'error_message', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'processed_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
final class PayInSchema
{
}
