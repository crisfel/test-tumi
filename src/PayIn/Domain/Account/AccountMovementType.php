<?php

declare(strict_types=1);

namespace PayIn\Domain\Account;

/**
 * Tipo de movimiento de saldo del libro mayor.
 */
enum AccountMovementType: string
{
    case CREDIT = 'credit';

    case DEBIT = 'debit';
}
