<?php

declare(strict_types=1);

namespace PayIn\Domain\Exceptions;

use PayIn\Domain\Currency;

/**
 * Se lanza cuando dos montos de monedas distintas interactúan entre sí.
 */
final class CurrencyMismatchException extends PayInDomainException
{
    public function __construct(Currency $expected, Currency $actual)
    {
        parent::__construct(
            sprintf('La moneda "%s" no coincide con la esperada ("%s").', $actual->value, $expected->value),
            'CURRENCY_MISMATCH',
            ['expected' => $expected->value, 'actual' => $actual->value],
        );
    }
}
