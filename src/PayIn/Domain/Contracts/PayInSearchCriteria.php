<?php

declare(strict_types=1);

namespace PayIn\Domain\Contracts;

use PayIn\Domain\Client\ClientId;
use PayIn\Domain\PayIn\PayInStatus;

/**
 * Criterio de búsqueda de PayIns (Specification de consulta).
 *
 * Definido en el dominio para que el contrato de repositorio no dependa del
 * Application layer. Permite filtrar el historial por cliente (client_id),
 * por estado y por rango de fechas.
 */
final readonly class PayInSearchCriteria
{
    public const DEFAULT_LIMIT = 20;

    public const MAX_LIMIT = 100;

    public function __construct(
        public ?ClientId $clientId = null,
        public ?PayInStatus $status = null,
        public ?\DateTimeImmutable $from = null,
        public ?\DateTimeImmutable $to = null,
        public int $limit = self::DEFAULT_LIMIT,
        public int $offset = 0,
    ) {
    }
}
