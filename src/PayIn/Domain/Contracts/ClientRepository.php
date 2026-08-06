<?php

declare(strict_types=1);

namespace PayIn\Domain\Contracts;

use PayIn\Domain\Client\Client;
use PayIn\Domain\Client\ClientId;

/**
 * Puertos de persistencia del dominio (Ports & Adapters).
 *
 * Las interfaces se definen en el dominio; sus implementaciones Eloquent
 * residen en Infrastructure. El dominio nunca conoce la infraestructura.
 */
interface ClientRepository
{
    public function findById(ClientId $id): ?Client;
}
