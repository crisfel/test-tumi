<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Repositories;

use PayIn\Domain\Account\Account;
use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Contracts\AccountRepository;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\AccountMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Models\AccountModel;

/**
 * Implementación Eloquent del puerto AccountRepository.
 *
 * La actualización de saldo no utiliza locking optimista: el abono ocurre
 * dentro de una transacción aislada; el locking a nivel de fila (SELECT ...
 * FOR UPDATE) se documenta como mejora futura para alta concurrencia.
 */
final readonly class EloquentAccountRepository implements AccountRepository
{
    public function __construct(private AccountMapper $mapper)
    {
    }

    public function findById(AccountId $id): ?Account
    {
        $model = AccountModel::query()->find($id->toString());

        return $model !== null ? $this->mapper->fromModel($model) : null;
    }

    public function save(Account $account): void
    {
        $model = $this->mapper->toModel($account);
        $existing = AccountModel::query()->find($account->id()->toString());

        if ($existing === null) {
            $model->save();

            return;
        }

        $existing->update([
            'balance' => $model->balance,
        ]);
    }
}
