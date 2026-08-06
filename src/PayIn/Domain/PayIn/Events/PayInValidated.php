<?php

declare(strict_types=1);

namespace PayIn\Domain\PayIn\Events;

use PayIn\Domain\PayIn\TransactionId;
use PayIn\Shared\Kernel\DomainEvent;

/**
 * Emitido cuando un PayIn supera las validaciones de dominio (VALIDATED).
 */
final readonly class PayInValidated implements DomainEvent
{
    public function __construct(public TransactionId $payInId)
    {
    }
}
