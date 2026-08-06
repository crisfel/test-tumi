<?php

declare(strict_types=1);

namespace PayIn\Domain\Exceptions;

/**
 * Se lanza cuando un código de proveedor no cumple el formato del dominio.
 */
final class InvalidProviderCodeException extends PayInDomainException
{
    public function __construct(string $value)
    {
        parent::__construct(
            sprintf('El código de proveedor "%s" no es válido (2-32 caracteres en minúsculas, números o "_").', $value),
            'PROVIDER_CODE_INVALID',
            ['value' => $value],
        );
    }
}
