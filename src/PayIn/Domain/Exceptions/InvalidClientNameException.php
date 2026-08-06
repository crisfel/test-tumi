<?php

declare(strict_types=1);

namespace PayIn\Domain\Exceptions;

/**
 * Se lanza cuando el nombre de un cliente no cumple las reglas del dominio.
 */
final class InvalidClientNameException extends PayInDomainException
{
    public function __construct(string $value)
    {
        parent::__construct(
            sprintf('El nombre de cliente "%s" no es válido (máximo 100 caracteres).', $value),
            'CLIENT_NAME_INVALID',
            ['value' => $value],
        );
    }
}
