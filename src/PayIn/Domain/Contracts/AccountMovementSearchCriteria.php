<?php

declare(strict_types=1);

namespace PayIn\Domain\Contracts;

use PayIn\Domain\Account\AccountId;

/**
 * Criterio de búsqueda de movimientos de una cuenta (extracto).
 */
final readonly class AccountMovementSearchCriteria
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
