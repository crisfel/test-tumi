<?php

declare(strict_types=1);

namespace PayIn\Domain\Exceptions;

/**
 * Se lanza cuando un string no representa una dirección de correo válida.
 */
final class InvalidEmailException extends PayInDomainException
{
    public function __construct(string $value)
    {
        parent::__construct(
            sprintf('La dirección de correo "%s" no es válida.', $value),
            'EMAIL_INVALID',
            ['value' => $value],
        );
    }
}
