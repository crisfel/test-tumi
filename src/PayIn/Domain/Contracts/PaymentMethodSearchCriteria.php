<?php

declare(strict_types=1);

namespace PayIn\Domain\Contracts;

use PayIn\Domain\Account\AccountId;

/**
 * Criterio de búsqueda de métodos de pago de una cuenta.
 *
 * Definido en el dominio para que el contrato de repositorio no dependa del
 * Application layer.
 */
final readonly class PaymentMethodSearchCriteria
{
    public const DEFAULT_LIMIT = 20;

    public const MAX_LIMIT = 100;

    public function __construct(
        public AccountId $accountId,
        public int $limit = self::DEFAULT_LIMIT,
        public int $offset = 0,
    ) {
    }
}
