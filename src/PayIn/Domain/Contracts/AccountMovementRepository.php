<?php

declare(strict_types=1);

namespace PayIn\Domain\Contracts;

use PayIn\Domain\Account\AccountMovement;

/**
 * Puertos de persistencia del libro mayor (Ports & Adapters).
 */
interface AccountMovementRepository
{
    public function save(AccountMovement $movement): void;

    /**
     * @return list<AccountMovement>
     */
    public function matching(AccountMovementSearchCriteria $criteria): array;

    public function countMatching(AccountMovementSearchCriteria $criteria): int;
}
