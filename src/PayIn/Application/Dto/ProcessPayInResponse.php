<?php

declare(strict_types=1);

namespace PayIn\Application\Dto;

use PayIn\Domain\PayIn\PayIn;
use PayIn\Domain\PayIn\PayInStatus;
use PayIn\Domain\PayIn\TransactionId;

/**
 * Respuesta del caso de uso ProcessPayIn: estado final de la operación.
 */
final readonly class ProcessPayInResponse
{
    public function __construct(
        public TransactionId $payInId,
        public PayInStatus $status,
        public ?string $providerTransactionId = null,
        public ?string $errorCode = null,
    ) {
    }

    public static function fromPayIn(PayIn $payIn): self
    {
        return new self(
            payInId: $payIn->id(),
            status: $payIn->status(),
            providerTransactionId: $payIn->providerTransactionId()?->value(),
            errorCode: $payIn->errorCode(),
        );
    }
}
