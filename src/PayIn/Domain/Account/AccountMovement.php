<?php

declare(strict_types=1);

namespace PayIn\Domain\Account;

use PayIn\Domain\Money;
use PayIn\Domain\PayIn\TransactionId;

/**
 * Entidad del libro mayor: cada movimiento de saldo de una cuenta.
 *
 * Registra débitos y créditos con el saldo resultante, lo que permite
 * auditar y reconstruir el historial de una cuenta (extractos).
 */
final readonly class AccountMovement
{
    public function __construct(
        private AccountMovementId $id,
        private AccountId $accountId,
        private AccountMovementType $type,
        private Money $amount,
        private Money $balanceAfter,
        private ?TransactionId $payInId,
        private \DateTimeImmutable $occurredAt,
    ) {
    }

    public static function record(
        AccountMovementId $id,
        AccountId $accountId,
        AccountMovementType $type,
        Money $amount,
        Money $balanceAfter,
        ?TransactionId $payInId,
        \DateTimeImmutable $occurredAt,
    ): self {
        return new self($id, $accountId, $type, $amount, $balanceAfter, $payInId, $occurredAt);
    }

    public function id(): AccountMovementId
    {
        return $this->id;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function type(): AccountMovementType
    {
        return $this->type;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function balanceAfter(): Money
    {
        return $this->balanceAfter;
    }

    public function payInId(): ?TransactionId
    {
        return $this->payInId;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
