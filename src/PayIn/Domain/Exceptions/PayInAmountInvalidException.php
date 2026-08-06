<?php

declare(strict_types=1);

namespace PayIn\Domain\Exceptions;

/**
 * El monto del PayIn debe ser mayor a cero.
 */
final class PayInAmountInvalidException extends PayInValidationException
{
    public function __construct(int $minorUnits)
    {
        parent::__construct(
            sprintf('El monto del PayIn debe ser mayor a cero (recibido: %d).', $minorUnits),
            'PAYIN_AMOUNT_INVALID',
            ['minor_units' => $minorUnits],
        );
    }
}
