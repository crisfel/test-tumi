<?php

declare(strict_types=1);

namespace PayIn\Application\Dto;

use PayIn\Domain\PaymentProvider\PaymentProvider;

/**
 * Página de resultados del catálogo de proveedores.
 */
final readonly class PaymentProviderPage
{
    /**
     * @param list<PaymentProvider> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $limit,
        public int $offset,
    ) {
    }
}
