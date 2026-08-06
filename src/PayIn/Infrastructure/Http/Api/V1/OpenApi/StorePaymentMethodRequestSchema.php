<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Schema OpenAPI del cuerpo de la petición POST /api/v1/payment-methods.
 */
#[OA\Schema(
    schema: 'StorePaymentMethodRequest',
    required: ['provider_code', 'type', 'token', 'details_masked'],
    properties: [
        new OA\Property(property: 'provider_code', description: 'Código del proveedor que tokeniza el método y procesará el cobro', type: 'string', example: 'fakepay'),
        new OA\Property(property: 'type', description: 'Tipo de método de pago (debe estar en las capacidades del proveedor)', type: 'string', enum: ['card', 'bank_transfer', 'wallet', 'pse', 'cash'], example: 'card'),
        new OA\Property(property: 'token', description: 'Token opaco de cobro emitido por el proveedor (nunca el PAN)', type: 'string', example: 'tok_card_visa_4242'),
        new OA\Property(property: 'details_masked', description: 'Detalle enmascarado para mostrar al cliente', type: 'string', example: '**** 4242'),
    ],
)]
final class StorePaymentMethodRequestSchema
{
}
