<?php

declare(strict_types=1);

namespace PayIn\Application\UseCase;

use PayIn\Application\Command\AdjustAccountBalanceCommand;
use PayIn\Application\Exception\AccountNotFoundException;
use PayIn\Application\Port\Clock;
use PayIn\Domain\Account\Account;
use PayIn\Domain\Account\AccountMovement;
use PayIn\Domain\Account\AccountMovementId;
use PayIn\Domain\Account\AccountMovementType;
use PayIn\Domain\Account\BalanceAdjustmentType;
use PayIn\Domain\Contracts\AccountMovementRepository;
use PayIn\Domain\Contracts\AccountRepository;

/**
 * Caso de uso: ajuste manual de saldo de una cuenta.
 *
 * INCREASE acredita (aumenta) el saldo; DECREASE debita (disminuye) y
 * requiere saldo suficiente (422 INSUFFICIENT_FUNDS). Cada ajuste queda
 * registrado en el libro mayor con referencia nula (no corresponde a un
 * PayIn).
 */
final readonly class AdjustAccountBalanceService
{
    public function __construct(
        private AccountRepository $accounts,
        private AccountMovementRepository $movements,
        private Clock $clock,
    ) {
    }

    public function adjust(AdjustAccountBalanceCommand $command): Account
    {
        $account = $this->accounts->findById($command->accountId)
            ?? throw new AccountNotFoundException($command->accountId->toString());

        $type = match ($command->type) {
            BalanceAdjustmentType::INCREASE => AccountMovementType::CREDIT,
            BalanceAdjustmentType::DECREASE => AccountMovementType::DEBIT,
        };

        if ($command->type === BalanceAdjustmentType::INCREASE) {
            $account->credit($command->amount);
        } else {
            $account->debit($command->amount);
        }

        $this->accounts->save($account);
        $this->movements->save(AccountMovement::record(
            AccountMovementId::generate(),
            $account->id(),
            $type,
            $command->amount,
            $account->balance(),
            null,
            $this->clock->now(),
        ));

        return $account;
    }
}
