<?php

declare(strict_types=1);

namespace PayIn\Application\Dto;

use PayIn\Domain\Account\Account;

/**
 * Página de resultados del listado de cuentas.
 */
final readonly class AccountPage
{
    /**
     * @param list<Account> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $limit,
        public int $offset,
    ) {
    }
}
