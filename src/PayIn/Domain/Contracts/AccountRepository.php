<?php

declare(strict_types=1);

namespace PayIn\Domain\Contracts;

use PayIn\Domain\Account\Account;
use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Currency;

/**
 * Puertos de persistencia del dominio (Ports & Adapters).
 */
interface AccountRepository
{
    public function findById(AccountId $id): ?Account;

    public function save(Account $account): void;

    public function existsByClientAndCurrency(ClientId $clientId, Currency $currency): bool;

    /**
     * @return list<Account>
     */
    public function matching(AccountSearchCriteria $criteria): array;

    public function countMatching(AccountSearchCriteria $criteria): int;
}
