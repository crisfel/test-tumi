<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Mappers;

use Illuminate\Support\Carbon;
use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Account\AccountMovement;
use PayIn\Domain\Account\AccountMovementId;
use PayIn\Domain\Account\AccountMovementType;
use PayIn\Domain\Currency;
use PayIn\Domain\Money;
use PayIn\Domain\PayIn\TransactionId;
use PayIn\Infrastructure\Persistence\Eloquent\Models\AccountMovementModel;

/**
 * Traduce entre la entidad AccountMovement y su representación persistente.
 */
final class AccountMovementMapper
{
    public function toModel(AccountMovement $movement): AccountMovementModel
    {
        $model = new AccountMovementModel();
        $model->id = $movement->id()->toString();
        $model->account_id = $movement->accountId()->toString();
        $model->type = $movement->type()->value;
        $model->amount = $movement->amount()->minorUnits();
        $model->currency = $movement->amount()->currency()->value;
        $model->balance_after = $movement->balanceAfter()->minorUnits();
        $model->pay_in_id = $movement->payInId()?->toString();
        $model->occurred_at = Carbon::instance($movement->occurredAt());

        return $model;
    }

    public function fromModel(AccountMovementModel $model): AccountMovement
    {
        $occurredAt = $model->occurred_at;
        $currency = Currency::fromCode($model->currency);

        return AccountMovement::record(
            AccountMovementId::fromString($model->id),
            AccountId::fromString($model->account_id),
            AccountMovementType::from($model->type),
            Money::fromMinorUnits((int) $model->amount, $currency),
            Money::fromMinorUnits((int) $model->balance_after, $currency),
            $model->pay_in_id !== null ? TransactionId::fromString($model->pay_in_id) : null,
            new \DateTimeImmutable($occurredAt->format('Y-m-d H:i:s.u')),
        );
    }
}
