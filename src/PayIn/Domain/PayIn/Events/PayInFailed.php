<?php

declare(strict_types=1);

namespace PayIn\Domain\PayIn\Events;

use PayIn\Domain\PayIn\TransactionId;
use PayIn\Shared\Kernel\DomainEvent;

/**
 * Emitido cuando el PayIn falla (rechazo, timeout o error).
 */
final readonly class PayInFailed implements DomainEvent
{
    public function __construct(
        public TransactionId $payInId,
        public string $errorCode,
        public string $errorMessage,
    ) {
    }
}
