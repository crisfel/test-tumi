<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Builder;
use PayIn\Domain\Account\AccountMovement;
use PayIn\Domain\Contracts\AccountMovementRepository;
use PayIn\Domain\Contracts\AccountMovementSearchCriteria;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\AccountMovementMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Models\AccountMovementModel;

/**
 * Implementación Eloquent del puerto AccountMovementRepository.
 */
final readonly class EloquentAccountMovementRepository implements AccountMovementRepository
{
    public function __construct(private AccountMovementMapper $mapper)
    {
    }

    public function save(AccountMovement $movement): void
    {
        $this->mapper->toModel($movement)->save();
    }

    public function matching(AccountMovementSearchCriteria $criteria): array
    {
        $models = $this->applyCriteria(AccountMovementModel::query(), $criteria)
            ->orderByDesc('occurred_at')
            ->limit($criteria->limit)
            ->offset($criteria->offset)
            ->get();

        $movements = [];

        foreach ($models as $model) {
            $movements[] = $this->mapper->fromModel($model);
        }

        return $movements;
    }

    public function countMatching(AccountMovementSearchCriteria $criteria): int
    {
        return $this->applyCriteria(AccountMovementModel::query(), $criteria)->count();
    }

    /**
     * @param Builder<AccountMovementModel> $query
     *
     * @return Builder<AccountMovementModel>
     */
    private function applyCriteria(Builder $query, AccountMovementSearchCriteria $criteria): Builder
    {
        return $query->where('account_id', $criteria->accountId->toString());
    }
}
