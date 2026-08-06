<?php

declare(strict_types=1);

namespace PayIn\Domain\PayIn;

/**
 * Tipo de transacción financiera.
 *
 * La tabla "transactions" es el núcleo financiero reutilizable: futuros
 * PAYOUTS o REFUNDS reutilizan el mismo modelo sin modificar el dominio.
 */
enum TransactionType: string
{
    case PAYIN = 'payin';
}
