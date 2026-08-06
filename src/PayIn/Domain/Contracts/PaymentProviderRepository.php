<?php

declare(strict_types=1);

namespace PayIn\Domain\Contracts;

use PayIn\Domain\PaymentProvider\PaymentProvider;
use PayIn\Domain\PaymentProvider\ProviderCode;
use PayIn\Domain\PaymentProvider\ProviderId;

/**
 * Puertos de persistencia del dominio (Ports & Adapters).
 */
interface PaymentProviderRepository
{
    public function findById(ProviderId $id): ?PaymentProvider;

    public function findByCode(ProviderCode $code): ?PaymentProvider;
}
