<?php

declare(strict_types=1);

namespace PayIn\Application\Command;

use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Currency;
use PayIn\Domain\Money;

/**
 * Comando inmutable que representa la intención de abrir una cuenta.
 */
final readonly class OpenAccountCommand
{
    public function __construct(
        public ClientId $clientId,
        public Currency $currency,
        public ?Money $initialBalance = null,
    ) {
    }
}
