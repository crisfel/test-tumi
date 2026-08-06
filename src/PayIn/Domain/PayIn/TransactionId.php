<?php

declare(strict_types=1);

namespace PayIn\Domain\PayIn;

use PayIn\Shared\Uuid\TypedId;

/**
 * Identificador de una transacción/PayIn.
 */
final readonly class TransactionId extends TypedId
{
}
