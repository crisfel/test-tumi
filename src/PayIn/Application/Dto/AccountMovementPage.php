<?php

declare(strict_types=1);

namespace PayIn\Application\Dto;

use PayIn\Domain\Account\AccountMovement;

/**
 * Página de resultados del extracto de una cuenta.
 */
final readonly class AccountMovementPage
{
    /**
     * @param list<AccountMovement> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $limit,
        public int $offset,
    ) {
    }
}
