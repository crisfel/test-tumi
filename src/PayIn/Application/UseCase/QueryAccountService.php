<?php

declare(strict_types=1);

namespace PayIn\Application\UseCase;

use PayIn\Application\Exception\AccountNotFoundException;
use PayIn\Domain\Account\Account;
use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Contracts\AccountRepository;

/**
 * Caso de uso de consulta de una cuenta por su identificador.
 */
final readonly class QueryAccountService
{
    public function __construct(private AccountRepository $accounts)
    {
    }

    public function findById(AccountId $id): ?Account
    {
        return $this->accounts->findById($id);
    }

    /**
     * @throws AccountNotFoundException
     */
    public function findByIdOrFail(AccountId $id): Account
    {
        return $this->accounts->findById($id)
            ?? throw new AccountNotFoundException($id->toString());
    }
}
