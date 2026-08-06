<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Schema OpenAPI del cuerpo de la petición POST /api/v1/payins.
 *
 * Los ejemplos corresponden a los datos sembrados por DemoSeeder para que
 * "Try it out" funcione sin modificaciones.
 */
#[OA\Schema(
    schema: 'StorePayInRequest',
    required: ['client_id', 'origin_account_id', 'account_id', 'payment_method_id', 'amount', 'currency'],
    properties: [
        new OA\Property(property: 'client_id', description: 'UUID del cliente que paga (originador)', type: 'string', format: 'uuid', example: '019fd715-ebf8-7223-ada8-b3c168a28e22'),
        new OA\Property(property: 'origin_account_id', description: 'UUID de la cuenta del pagador: se DEBITA su saldo y debe pertenecer al cliente', type: 'string', format: 'uuid', example: '019fd715-ec1a-7a7e-ab6f-f497aa52abe4'),
        new OA\Property(property: 'account_id', description: 'UUID de la cuenta destino: se ACREDITA su saldo (puede ser de cualquier cliente)', type: 'string', format: 'uuid', example: '019fd715-ec22-700c-8cba-ea026d0fd9a9'),
        new OA\Property(property: 'payment_method_id', description: 'UUID del método de pago (instrumento)', type: 'string', format: 'uuid', example: '019fd715-ec43-784b-97dd-9b2fe70bfe69'),
        new OA\Property(property: 'amount', description: 'Monto en unidades menores (enteras) de la moneda', type: 'integer', example: 25000),
        new OA\Property(property: 'currency', description: 'Moneda ISO 4217', type: 'string', enum: ['COP', 'USD', 'EUR', 'MXN'], example: 'COP'),
        new OA\Property(property: 'reference', description: 'Referencia idempotente del cliente (opcional)', type: 'string', example: 'order-2026-0001'),
    ],
)]
final class StorePayInRequestSchema
{
}
