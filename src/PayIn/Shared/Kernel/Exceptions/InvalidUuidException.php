<?php

declare(strict_types=1);

namespace PayIn\Shared\Kernel\Exceptions;

/**
 * Se lanza cuando un string no representa un UUID válido.
 */
final class InvalidUuidException extends DomainException
{
    public function __construct(string $value)
    {
        parent::__construct(
            sprintf('El valor "%s" no es un UUID válido.', $value),
            'UUID_INVALID',
            ['value' => $value],
        );
    }
}
