<?php

declare(strict_types=1);

namespace PayIn\Application\Dto;

use PayIn\Domain\PaymentMethod\PaymentMethod;

/**
 * Página de resultados del listado de métodos de pago.
 */
final readonly class PaymentMethodPage
{
    /**
     * @param list<PaymentMethod> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $limit,
        public int $offset,
    ) {
    }
}
