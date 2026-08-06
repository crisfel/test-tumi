<?php

declare(strict_types=1);

namespace PayIn\Domain\Contracts;

use PayIn\Domain\Client\ClientId;

/**
 * Criterio de búsqueda de cuentas de un cliente.
 *
 * Definido en el dominio para que el contrato de repositorio no dependa del
 * Application layer.
 */
final readonly class AccountSearchCriteria
{
    public const DEFAULT_LIMIT = 20;

    public const MAX_LIMIT = 100;

    public function __construct(
        public ClientId $clientId,
        public int $limit = self::DEFAULT_LIMIT,
        public int $offset = 0,
    ) {
    }
}
