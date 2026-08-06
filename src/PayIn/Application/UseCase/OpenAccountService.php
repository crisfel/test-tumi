<?php

declare(strict_types=1);

namespace PayIn\Application\UseCase;

use PayIn\Application\Command\OpenAccountCommand;
use PayIn\Application\Exception\AccountAlreadyExistsException;
use PayIn\Application\Exception\ClientNotFoundException;
use PayIn\Application\Port\Clock;
use PayIn\Domain\Account\Account;
use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Account\AccountMovement;
use PayIn\Domain\Account\AccountMovementId;
use PayIn\Domain\Account\AccountMovementType;
use PayIn\Domain\Contracts\AccountMovementRepository;
use PayIn\Domain\Contracts\AccountRepository;
use PayIn\Domain\Contracts\ClientRepository;

/**
 * Caso de uso: abrir una cuenta para un cliente.
 *
 * Valida la existencia del cliente y la unicidad (cliente, moneda), abre
 * la cuenta con saldo inicial opcional y la persiste. Si se asigna saldo
 * inicial, se registra el movimiento de apertura en el libro mayor. La
 * violación de la restricción UNIQUE en BD también se traduce en
 * AccountAlreadyExistsException.
 */
final readonly class OpenAccountService
{
    public function __construct(
        private ClientRepository $clients,
        private AccountRepository $accounts,
        private AccountMovementRepository $movements,
        private Clock $clock,
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
            $command->initialBalance,
        );

        $this->accounts->save($account);

        if ($command->initialBalance instanceof \PayIn\Domain\Money && $command->initialBalance->isPositive()) {
            $this->movements->save(AccountMovement::record(
                AccountMovementId::generate(),
                $account->id(),
                AccountMovementType::CREDIT,
                $command->initialBalance,
                $account->balance(),
                null,
                $this->clock->now(),
            ));
        }

        return $account;
    }
}
