<?php

declare(strict_types=1);

namespace PayIn\Application\Exception;

/**
 * El método de pago solicitado no existe en la plataforma.
 */
final class PaymentMethodNotFoundException extends PayInApplicationException
{
    public function __construct(string $paymentMethodId)
    {
        parent::__construct(
            sprintf('El método de pago "%s" no existe.', $paymentMethodId),
            'PAYMENT_METHOD_NOT_FOUND',
            ['payment_method_id' => $paymentMethodId],
        );
    }
}
