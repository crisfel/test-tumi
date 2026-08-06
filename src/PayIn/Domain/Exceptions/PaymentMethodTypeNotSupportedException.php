<?php

declare(strict_types=1);

namespace PayIn\Domain\Exceptions;

/**
 * El proveedor de pago no soporta el tipo de método de pago de la
 * operación (matriz de capacidades del proveedor).
 */
final class PaymentMethodTypeNotSupportedException extends PayInValidationException
{
    public function __construct(string $methodType, string $providerCode)
    {
        parent::__construct(
            sprintf('El proveedor de pago "%s" no soporta el tipo de método "%s".', $providerCode, $methodType),
            'PAYMENT_METHOD_TYPE_NOT_SUPPORTED',
            [
                'method_type' => $methodType,
                'provider' => $providerCode,
            ],
        );
    }
}
