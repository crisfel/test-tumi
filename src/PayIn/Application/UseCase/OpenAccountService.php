<?php

declare(strict_types=1);

namespace PayIn\Application\UseCase;

use PayIn\Application\Command\OpenAccountCommand;
use PayIn\Application\Exception\AccountAlreadyExistsException;
use PayIn\Application\Exception\ClientNotFoundException;
use PayIn\Domain\Account\Account;
use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Contracts\AccountRepository;
use PayIn\Domain\Contracts\ClientRepository;

/**
 * Caso de uso: abrir una cuenta para un cliente.
 *
 * Valida la existencia del cliente y la unicidad (cliente, moneda), abre
 * la cuenta con saldo cero y la persiste. La violación de la restricción
 * UNIQUE en BD también se traduce en AccountAlreadyExistsException.
 */
final readonly class OpenAccountService
{
    public function __construct(
        private ClientRepository $clients,
        private AccountRepository $accounts,
    ) {
    }

    public function open(OpenAccountCommand $command): Account
    {
        if (!$this->clients->findById($command->clientId) instanceof \PayIn\Domain\Client\Client) {
            throw new ClientNotFoundException($command->clientId->toString());
        }

        if ($this->accounts->existsByClientAndCurrency($command->clientId, $command->currency)) {
            throw new AccountAlreadyExistsException(
                $command->clientId->toString(),
                $command->currency->value,
            );
        }

        $account = Account::open(
            AccountId::generate(),
            $command->clientId,
            $command->currency,
        );

        $this->accounts->save($account);

        return $account;
    }
}
