<?php

declare(strict_types=1);

namespace PayIn\Domain\PayIn\Events;

use PayIn\Domain\PayIn\ProviderTransactionId;
use PayIn\Domain\PayIn\TransactionId;
use PayIn\Shared\Kernel\DomainEvent;

/**
 * Emitido cuando el proveedor confirma el cobro (PROCESSED).
 */
final readonly class PayInProcessed implements DomainEvent
{
    public function __construct(
        public TransactionId $payInId,
        public ProviderTransactionId $providerTransactionId,
    ) {
    }
}
