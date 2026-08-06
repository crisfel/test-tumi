<?php

declare(strict_types=1);

namespace PayIn\Domain\Contracts;

use PayIn\Domain\PayIn\PayIn;
use PayIn\Domain\PayIn\Reference;
use PayIn\Domain\PayIn\TransactionId;

/**
 * Puertos de persistencia del dominio (Ports & Adapters).
 */
interface PayInRepository
{
    public function save(PayIn $payIn): void;

    public function findById(TransactionId $id): ?PayIn;

    public function existsByReference(Reference $reference): bool;

    /**
     * @return list<PayIn>
     */
    public function matching(PayInSearchCriteria $criteria): array;

    public function countMatching(PayInSearchCriteria $criteria): int;
}
