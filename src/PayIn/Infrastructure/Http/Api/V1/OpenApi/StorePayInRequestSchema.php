<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Schema OpenAPI del cuerpo de la petición POST /api/v1/payins.
 *
 * Los ejemplos corresponden EXACTAMENTE a los datos sembrados por los
 * seeders (DatabaseSeeder), así que "Try it out" funciona sin modificaciones:
 * Ana paga con su tarjeta Visa y el dinero llega a la cuenta COP de Pedro.
 */
#[OA\Schema(
    schema: 'StorePayInRequest',
    required: ['client_id', 'origin_account_id', 'account_id', 'payment_method_id', 'amount', 'currency'],
    properties: [
        new OA\Property(property: 'client_id', description: 'UUID del cliente que paga (Ana García) — su cuenta de origen se debita', type: 'string', format: 'uuid', example: '019fd715-ebf8-7223-ada8-b3c168a28e22'),
        new OA\Property(property: 'origin_account_id', description: 'UUID de la cuenta de ORIGEN de Ana (COP, saldo 100.000): se DEBITA el monto', type: 'string', format: 'uuid', example: '019fd715-ec1a-7a7e-ab6f-f497aa52abe4'),
        new OA\Property(property: 'account_id', description: 'UUID de la cuenta DESTINO de Pedro (COP, saldo 0): se ACREDITA el monto', type: 'string', format: 'uuid', example: '019fd715-ec22-700c-8cba-ea026d0fd9a9'),
        new OA\Property(property: 'payment_method_id', description: 'UUID del método de pago (tarjeta Visa de FakePay)', type: 'string', format: 'uuid', example: '019fd715-ec43-784b-97dd-9b2fe70bfe69'),
        new OA\Property(property: 'amount', description: 'Monto en unidades menores (enteras) de la moneda. Ej.: 1000 COP = $10,00 COP', type: 'integer', example: 25000),
        new OA\Property(property: 'currency', description: 'Moneda ISO 4217 (debe coincidir con la de ambas cuentas)', type: 'string', enum: ['COP', 'USD', 'EUR', 'MXN'], example: 'COP'),
        new OA\Property(property: 'reference', description: 'Referencia idempotente del cliente (opcional; si la repites devuelve 409)', type: 'string', example: 'order-2026-0001'),
    ],
)]
final class StorePayInRequestSchema
{
}
