<?php

declare(strict_types=1);

namespace PayIn\Domain\Contracts;

use PayIn\Domain\PaymentMethod\PaymentMethodType;
use PayIn\Domain\PaymentProvider\ProviderCode;
use PayIn\Domain\PaymentProvider\ProviderId;

/**
 * Criterio de búsqueda de métodos de pago.
 *
 * Los métodos son instrumentos independientes: se listan globalmente con
 * filtros opcionales de tipo y proveedor (por código; el Application layer
 * resuelve el código a identificador persistido).
 */
final readonly class PaymentMethodSearchCriteria
{
    public const DEFAULT_LIMIT = 20;

    public const MAX_LIMIT = 100;

    public function __construct(
        public ?PaymentMethodType $type = null,
        public ?ProviderCode $providerCode = null,
        public ?ProviderId $providerId = null,
        public int $limit = self::DEFAULT_LIMIT,
        public int $offset = 0,
    ) {
    }
}
