<?php

declare(strict_types=1);

namespace PayIn\Application\Exception;

/**
 * La referencia de idempotencia ya fue utilizada por otra operación.
 *
 * Previene la duplicación de cobros cuando el cliente reintenta una petición
 * con la misma referencia (respuesta 409 Conflict).
 */
final class ReferenceAlreadyUsedException extends PayInApplicationException
{
    public function __construct(string $reference)
    {
        parent::__construct(
            sprintf('La referencia "%s" ya fue utilizada en otra operación.', $reference),
            'REFERENCE_ALREADY_USED',
            ['reference' => $reference],
        );
    }
}
