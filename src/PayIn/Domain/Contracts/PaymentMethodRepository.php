<?php

declare(strict_types=1);

namespace PayIn\Domain\Contracts;

use PayIn\Domain\PaymentMethod\PaymentMethod;
use PayIn\Domain\PaymentMethod\PaymentMethodId;
use PayIn\Domain\PaymentProvider\ProviderId;

/**
 * Puertos de persistencia del dominio (Ports & Adapters).
 */
interface PaymentMethodRepository
{
    public function findById(PaymentMethodId $id): ?PaymentMethod;

    public function save(PaymentMethod $method): void;

    public function existsByProviderAndToken(ProviderId $providerId, string $token): bool;

    /**
     * @return list<PaymentMethod>
     */
    public function matching(PaymentMethodSearchCriteria $criteria): array;

    public function countMatching(PaymentMethodSearchCriteria $criteria): int;
}
