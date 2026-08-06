<?php

declare(strict_types=1);

namespace PayIn\Domain\Exceptions;

/**
 * Se lanza cuando una referencia no cumple el formato del dominio.
 */
final class InvalidReferenceException extends PayInDomainException
{
    public function __construct(string $value)
    {
        parent::__construct(
            sprintf('La referencia "%s" no cumple el formato requerido (4-64 caracteres alfanuméricos, "_" o "-").', $value),
            'REFERENCE_INVALID',
            ['value' => $value],
        );
    }
}
