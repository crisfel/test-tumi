<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use PayIn\Application\Exception\AccountAlreadyExistsException;
use PayIn\Domain\Account\Account;
use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Contracts\AccountRepository;
use PayIn\Domain\Contracts\AccountSearchCriteria;
use PayIn\Domain\Currency;
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
            try {
                $model->save();

                return;
            } catch (QueryException $exception) {
                if ($this->isUniqueViolation($exception)) {
                    throw new AccountAlreadyExistsException(
                        $account->clientId()->toString(),
                        $account->currency()->value,
                    );
                }

                throw $exception;
            }
        }

        $existing->update([
            'balance' => $model->balance,
        ]);
    }

    public function existsByClientAndCurrency(ClientId $clientId, Currency $currency): bool
    {
        return AccountModel::query()
            ->where('client_id', $clientId->toString())
            ->where('currency', $currency->value)
            ->exists();
    }

    public function matching(AccountSearchCriteria $criteria): array
    {
        $models = $this->applyCriteria(AccountModel::query(), $criteria)
            ->orderByDesc('created_at')
            ->limit($criteria->limit)
            ->offset($criteria->offset)
            ->get();

        $accounts = [];

        foreach ($models as $model) {
            $accounts[] = $this->mapper->fromModel($model);
        }

        return $accounts;
    }

    public function countMatching(AccountSearchCriteria $criteria): int
    {
        return $this->applyCriteria(AccountModel::query(), $criteria)->count();
    }

    /**
     * @param Builder<AccountModel> $query
     *
     * @return Builder<AccountModel>
     */
    private function applyCriteria(Builder $query, AccountSearchCriteria $criteria): Builder
    {
        return $query->where('client_id', $criteria->clientId->toString());
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'client_id');
    }
}
