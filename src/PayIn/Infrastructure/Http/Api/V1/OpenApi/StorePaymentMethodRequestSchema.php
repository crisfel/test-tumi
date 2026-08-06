<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Schema OpenAPI del cuerpo de la petición POST /api/v1/payment-methods.
 */
#[OA\Schema(
    schema: 'StorePaymentMethodRequest',
    required: ['account_id', 'provider_code', 'type', 'token', 'details_masked'],
    properties: [
        new OA\Property(property: 'account_id', description: 'UUID de la cuenta titular', type: 'string', format: 'uuid', example: '019fd715-ec1a-7a7e-ab6f-f497aa52abe4'),
        new OA\Property(property: 'provider_code', description: 'Código del proveedor que procesará el cobro', type: 'string', example: 'fakepay'),
        new OA\Property(property: 'type', description: 'Tipo de método de pago', type: 'string', enum: ['card', 'bank_transfer', 'wallet', 'pse'], example: 'card'),
        new OA\Property(property: 'token', description: 'Token opaco de cobro (nunca el PAN)', type: 'string', example: 'tok_card_visa_4242'),
        new OA\Property(property: 'details_masked', description: 'Detalle enmascarado para mostrar al cliente', type: 'string', example: '**** 4242'),
    ],
)]
final class StorePaymentMethodRequestSchema
{
}
