<?php

declare(strict_types=1);

namespace PayIn\Domain\Exceptions;

/**
 * Se lanza cuando el identificador devuelto por un proveedor es inválido.
 */
final class InvalidProviderTransactionIdException extends PayInDomainException
{
    public function __construct(string $value)
    {
        parent::__construct(
            sprintf('El identificador de transacción del proveedor "%s" no es válido.', $value),
            'PROVIDER_TX_ID_INVALID',
            ['value' => $value],
        );
    }
}
