<?php

declare(strict_types=1);

namespace PayIn\Domain\Account;

/**
 * Dirección de un ajuste manual de saldo.
 */
enum BalanceAdjustmentType: string
{
    /**
     * Aumenta el saldo de la cuenta (crédito).
     */
    case INCREASE = 'increase';

    /**
     * Disminuye el saldo de la cuenta (débito).
     */
    case DECREASE = 'decrease';
}
