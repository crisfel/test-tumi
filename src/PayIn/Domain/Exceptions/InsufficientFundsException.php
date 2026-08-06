<?php

declare(strict_types=1);

namespace PayIn\Domain\Exceptions;

/**
 * El saldo de la cuenta no alcanza para la operación solicitada.
 */
final class InsufficientFundsException extends PayInValidationException
{
    public function __construct(int $available, int $required)
    {
        parent::__construct(
            sprintf('Saldo insuficiente: se requieren %d unidades y el saldo disponible es %d.', $required, $available),
            'INSUFFICIENT_FUNDS',
            [
                'available' => $available,
                'required' => $required,
            ],
        );
    }
}
