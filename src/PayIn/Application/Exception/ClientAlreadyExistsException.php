<?php

declare(strict_types=1);

namespace PayIn\Application\Exception;

/**
 * El email ya está registrado por otro cliente.
 *
 * El email es el identificador natural del cliente (único en BD);
 * duplicarlo viola la unicidad (respuesta 409 Conflict).
 */
final class ClientAlreadyExistsException extends PayInApplicationException
{
    public function __construct(string $email)
    {
        parent::__construct(
            sprintf('Ya existe un cliente registrado con el email "%s".', $email),
            'CLIENT_ALREADY_EXISTS',
            ['email' => $email],
        );
    }
}
