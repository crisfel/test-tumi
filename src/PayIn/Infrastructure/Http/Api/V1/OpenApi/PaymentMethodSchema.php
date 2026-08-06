<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Schema OpenAPI de la representación de un método de pago.
 *
 * El token nunca se expone en las respuestas.
 */
#[OA\Schema(
    schema: 'PaymentMethod',
    required: ['id', 'provider_id', 'type', 'details_masked', 'is_active', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '019fd715-ec43-784b-97dd-9b2fe70bfe69'),
        new OA\Property(property: 'provider_id', description: 'Proveedor que tokenizó el método', type: 'string', format: 'uuid', example: '019fd715-eb24-7683-b8d2-9d83ffdca22d'),
        new OA\Property(property: 'type', type: 'string', enum: ['card', 'bank_transfer', 'wallet', 'pse', 'cash'], example: 'card'),
        new OA\Property(property: 'details_masked', type: 'string', example: '**** 4242'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-08-06T12:54:23Z'),
    ],
)]
final class PaymentMethodSchema
{
}
