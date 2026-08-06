<?php

declare(strict_types=1);

namespace PayIn\Domain\Exceptions;

/**
 * El método de pago se encuentra desactivado y no puede procesar cobros.
 */
final class PaymentMethodInactiveException extends PayInValidationException
{
    public function __construct(string $payInId)
    {
        parent::__construct(
            sprintf('El método de pago del PayIn "%s" se encuentra inactivo.', $payInId),
            'PAYMENT_METHOD_INACTIVE',
            ['payin_id' => $payInId],
        );
    }
}
