<?php

declare(strict_types=1);

namespace PayIn\Domain\PaymentProvider;

use PayIn\Shared\Uuid\TypedId;

/**
 * Identificador de un proveedor de pago.
 */
final readonly class ProviderId extends TypedId
{
}
