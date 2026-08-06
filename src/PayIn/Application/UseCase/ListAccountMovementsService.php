<?php

declare(strict_types=1);

namespace PayIn\Application\UseCase;

use PayIn\Application\Dto\AccountMovementPage;
use PayIn\Application\Exception\AccountNotFoundException;
use PayIn\Domain\Contracts\AccountMovementRepository;
use PayIn\Domain\Contracts\AccountMovementSearchCriteria;
use PayIn\Domain\Contracts\AccountRepository;

/**
 * Caso de uso de consulta del extracto (movimientos) de una cuenta.
 *
 * Valida que la cuenta exista (404) y devuelve sus movimientos paginados
 * del más reciente al más antiguo.
 */
final readonly class ListAccountMovementsService
{
    public function __construct(
        private AccountRepository $accounts,
        private AccountMovementRepository $movements,
    ) {
    }

    public function execute(AccountMovementSearchCriteria $criteria): AccountMovementPage
    {
        if (!$this->accounts->findById($criteria->accountId) instanceof \PayIn\Domain\Account\Account) {
            throw new AccountNotFoundException($criteria->accountId->toString());
        }

        return new AccountMovementPage(
            items: $this->movements->matching($criteria),
            total: $this->movements->countMatching($criteria),
            limit: $criteria->limit,
            offset: $criteria->offset,
        );
    }
}
