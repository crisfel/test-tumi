<?php

declare(strict_types=1);

namespace PayIn\Domain\Exceptions;

/**
 * El método de pago no pertenece a la cuenta destino del PayIn.
 */
final class PaymentMethodNotBelongingToAccountException extends PayInValidationException
{
    public function __construct(string $payInId)
    {
        parent::__construct(
            sprintf('El método de pago no pertenece a la cuenta destino del PayIn "%s".', $payInId),
            'PAYMENT_METHOD_NOT_OWNED',
            ['payin_id' => $payInId],
        );
    }
}
