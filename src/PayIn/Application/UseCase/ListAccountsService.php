<?php

declare(strict_types=1);

namespace PayIn\Application\UseCase;

use PayIn\Application\Dto\AccountPage;
use PayIn\Application\Exception\ClientNotFoundException;
use PayIn\Domain\Contracts\AccountRepository;
use PayIn\Domain\Contracts\AccountSearchCriteria;
use PayIn\Domain\Contracts\ClientRepository;

/**
 * Caso de uso de listado de cuentas de un cliente.
 *
 * Valida que el cliente exista (404) y devuelve sus cuentas paginadas.
 */
final readonly class ListAccountsService
{
    public function __construct(
        private ClientRepository $clients,
        private AccountRepository $accounts,
    ) {
    }

    public function execute(AccountSearchCriteria $criteria): AccountPage
    {
        if (!$this->clients->findById($criteria->clientId) instanceof \PayIn\Domain\Client\Client) {
            throw new ClientNotFoundException($criteria->clientId->toString());
        }

        return new AccountPage(
            items: $this->accounts->matching($criteria),
            total: $this->accounts->countMatching($criteria),
            limit: $criteria->limit,
            offset: $criteria->offset,
        );
    }
}
