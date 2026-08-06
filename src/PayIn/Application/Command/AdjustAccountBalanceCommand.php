<?php

declare(strict_types=1);

namespace PayIn\Application\Command;

use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Account\BalanceAdjustmentType;
use PayIn\Domain\Money;

/**
 * Comando inmutable que representa un ajuste manual de saldo.
 */
final readonly class AdjustAccountBalanceCommand
{
    public function __construct(
        public AccountId $accountId,
        public Money $amount,
        public BalanceAdjustmentType $type,
    ) {
    }
}
