<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Schema OpenAPI de la representación de un PayIn.
 */
#[OA\Schema(
    schema: 'PayIn',
    required: ['id', 'client_id', 'account_id', 'payment_method_id', 'amount', 'currency', 'status', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '019fd738-d4f2-7bc9-916b-0ae687b42038'),
        new OA\Property(property: 'client_id', type: 'string', format: 'uuid', example: '019fd715-ebf8-7223-ada8-b3c168a28e22'),
        new OA\Property(property: 'origin_account_id', description: 'Cuenta del pagador Ana (se debita)', type: 'string', format: 'uuid', example: '019fd715-ec1a-7a7e-ab6f-f497aa52abe4'),
        new OA\Property(property: 'account_id', description: 'Cuenta destino Pedro (se abona)', type: 'string', format: 'uuid', example: '019fd715-ec22-700c-8cba-ea026d0fd9a9'),
        new OA\Property(property: 'payment_method_id', type: 'string', format: 'uuid', example: '019fd715-ec43-784b-97dd-9b2fe70bfe69'),
        new OA\Property(property: 'amount', type: 'integer', example: 25000),
        new OA\Property(property: 'currency', type: 'string', enum: ['COP', 'USD', 'EUR', 'MXN'], example: 'COP'),
        new OA\Property(property: 'status', type: 'string', enum: ['created', 'validated', 'processing', 'processed', 'failed'], example: 'processed'),
        new OA\Property(property: 'reference', type: 'string', nullable: true, example: 'order-2026-0001'),
        new OA\Property(property: 'provider_id', type: 'string', format: 'uuid', nullable: true, example: '019fd715-eb24-7683-b8d2-9d83ffdca22d'),
        new OA\Property(property: 'provider_transaction_id', type: 'string', nullable: true, example: 'FP-019FD738-D4F'),
        new OA\Property(property: 'error_code', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'error_message', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-08-06T12:54:23Z'),
        new OA\Property(property: 'processed_at', type: 'string', format: 'date-time', nullable: true, example: '2026-08-06T12:54:23Z'),
    ],
)]
final class PayInSchema
{
}
