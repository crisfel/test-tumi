<?php

declare(strict_types=1);

namespace PayIn\Application\Dto;

use PayIn\Domain\PayIn\PayIn;

/**
 * Página de resultados del listado de PayIns.
 */
final readonly class PayInPage
{
    /**
     * @param list<PayIn> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $limit,
        public int $offset,
    ) {
    }
}
