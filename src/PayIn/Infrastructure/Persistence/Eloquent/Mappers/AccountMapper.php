<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Mappers;

use PayIn\Domain\Account\Account;
use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Currency;
use PayIn\Domain\Money;
use PayIn\Infrastructure\Persistence\Eloquent\Models\AccountModel;

/**
 * Traduce entre el aggregate Account y su representación persistente.
 */
final class AccountMapper
{
    public function toModel(Account $account): AccountModel
    {
        $model = new AccountModel();
        $model->id = $account->id()->toString();
        $model->client_id = $account->clientId()->toString();
        $model->currency = $account->currency()->value;
        $model->balance = $account->balance()->minorUnits();

        return $model;
    }

    public function fromModel(AccountModel $model): Account
    {
        return Account::reconstitute(
            AccountId::fromString($model->id),
            ClientId::fromString($model->client_id),
            Currency::fromCode($model->currency),
            Money::fromMinorUnits((int) $model->balance, Currency::fromCode($model->currency)),
        );
    }
}
