<?php

declare(strict_types=1);

namespace PayIn\Domain\Exceptions;

/**
 * Se lanza cuando un código no corresponde a una moneda soportada.
 */
final class InvalidCurrencyException extends PayInDomainException
{
    public function __construct(string $code)
    {
        parent::__construct(
            sprintf('La moneda "%s" no está soportada por la plataforma.', $code),
            'CURRENCY_INVALID',
            ['value' => $code],
        );
    }
}
