<?php

declare(strict_types=1);

namespace PayIn\Domain\Exceptions;

use PayIn\Domain\Currency;

/**
 * Se lanza cuando un monto es inválido para la operación (p. ej. negativo).
 */
final class InvalidAmountException extends PayInDomainException
{
    public function __construct(int $minorUnits, ?Currency $currency = null)
    {
        parent::__construct(
            sprintf('El monto %d (%s) no es válido.', $minorUnits, $currency?->value ?? 'sin moneda'),
            'AMOUNT_INVALID',
            [
                'minor_units' => $minorUnits,
                'currency' => $currency?->value,
            ],
        );
    }
}
