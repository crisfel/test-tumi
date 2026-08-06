<?php

declare(strict_types=1);

namespace PayIn\Domain\Contracts;

use PayIn\Domain\PaymentMethod\PaymentMethod;
use PayIn\Domain\PaymentMethod\PaymentMethodId;

/**
 * Puertos de persistencia del dominio (Ports & Adapters).
 */
interface PaymentMethodRepository
{
    public function findById(PaymentMethodId $id): ?PaymentMethod;
}
