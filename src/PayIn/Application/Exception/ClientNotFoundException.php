<?php

declare(strict_types=1);

namespace PayIn\Application\Exception;

/**
 * El cliente solicitado no existe en la plataforma.
 */
final class ClientNotFoundException extends PayInApplicationException
{
    public function __construct(string $clientId)
    {
        parent::__construct(
            sprintf('El cliente "%s" no existe.', $clientId),
            'CLIENT_NOT_FOUND',
            ['client_id' => $clientId],
        );
    }
}
