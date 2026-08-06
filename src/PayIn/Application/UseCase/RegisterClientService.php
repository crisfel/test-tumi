<?php

declare(strict_types=1);

namespace PayIn\Application\UseCase;

use PayIn\Application\Command\RegisterClientCommand;
use PayIn\Application\Exception\ClientAlreadyExistsException;
use PayIn\Domain\Client\Client;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Contracts\ClientRepository;

/**
 * Caso de uso: registrar un nuevo cliente.
 *
 * Verifica la unicidad del email, construye el aggregate y lo persiste.
 * El registro es atómico respecto a la unicidad: la violación de la
 * restricción en BD también se traduce en ClientAlreadyExistsException.
 */
final readonly class RegisterClientService
{
    public function __construct(private ClientRepository $clients)
    {
    }

    public function register(RegisterClientCommand $command): Client
    {
        if ($this->clients->existsByEmail($command->email)) {
            throw new ClientAlreadyExistsException($command->email->value());
        }

        $client = Client::register(
            ClientId::generate(),
            $command->name,
            $command->email,
        );

        $this->clients->save($client);

        return $client;
    }
}
