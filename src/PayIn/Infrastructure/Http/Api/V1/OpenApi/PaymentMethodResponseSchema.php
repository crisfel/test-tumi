<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Schema OpenAPI del envelope de respuesta de un método de pago.
 */
#[OA\Schema(
    schema: 'PaymentMethodResponse',
    required: ['data'],
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/PaymentMethod'),
    ],
)]
final class PaymentMethodResponseSchema
{
}
