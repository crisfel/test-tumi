<?php

declare(strict_types=1);

namespace PayIn\Domain\PaymentMethod;

use PayIn\Shared\Uuid\TypedId;

/**
 * Identificador de un método de pago.
 */
final readonly class PaymentMethodId extends TypedId
{
}
