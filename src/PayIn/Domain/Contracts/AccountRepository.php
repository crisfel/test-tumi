<?php

declare(strict_types=1);

namespace PayIn\Domain\Contracts;

use PayIn\Domain\Account\Account;
use PayIn\Domain\Account\AccountId;

/**
 * Puertos de persistencia del dominio (Ports & Adapters).
 */
interface AccountRepository
{
    public function findById(AccountId $id): ?Account;

    public function save(Account $account): void;
}
