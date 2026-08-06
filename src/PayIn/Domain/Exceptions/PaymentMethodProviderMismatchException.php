<?php

declare(strict_types=1);

namespace PayIn\Domain\Exceptions;

/**
 * El método de pago no corresponde al proveedor resuelto para la operación.
 */
final class PaymentMethodProviderMismatchException extends PayInValidationException
{
    public function __construct(string $payInId)
    {
        parent::__construct(
            sprintf('El método de pago del PayIn "%s" no corresponde al proveedor de la operación.', $payInId),
            'PAYMENT_METHOD_PROVIDER_MISMATCH',
            ['payin_id' => $payInId],
        );
    }
}
