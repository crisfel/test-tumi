<?php

declare(strict_types=1);

namespace PayIn\Domain\PayIn\Events;

use PayIn\Domain\PayIn\TransactionId;
use PayIn\Shared\Kernel\DomainEvent;

/**
 * Emitido cuando el cobro se delega al proveedor (PROCESSING).
 */
final readonly class PayInProcessing implements DomainEvent
{
    public function __construct(public TransactionId $payInId)
    {
    }
}
