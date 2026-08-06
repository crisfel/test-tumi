<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use PayIn\Application\Exception\PaymentMethodAlreadyExistsException;
use PayIn\Domain\Contracts\PaymentMethodRepository;
use PayIn\Domain\Contracts\PaymentMethodSearchCriteria;
use PayIn\Domain\PaymentMethod\PaymentMethod;
use PayIn\Domain\PaymentMethod\PaymentMethodId;
use PayIn\Domain\PaymentProvider\ProviderId;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\PaymentMethodMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Models\PaymentMethodModel;

/**
 * Implementación Eloquent del puerto PaymentMethodRepository.
 */
final readonly class EloquentPaymentMethodRepository implements PaymentMethodRepository
{
    public function __construct(private PaymentMethodMapper $mapper)
    {
    }

    public function findById(PaymentMethodId $id): ?PaymentMethod
    {
        $model = PaymentMethodModel::query()->find($id->toString());

        return $model !== null ? $this->mapper->fromModel($model) : null;
    }

    public function save(PaymentMethod $method): void
    {
        $model = $this->mapper->toModel($method);
        $existing = PaymentMethodModel::query()->find($method->id()->toString());

        if ($existing === null) {
            try {
                $model->save();

                return;
            } catch (QueryException $exception) {
                if ($this->isTokenViolation($exception)) {
                    throw new PaymentMethodAlreadyExistsException(
                        $method->providerId()->toString(),
                        $method->token(),
                    );
                }

                throw $exception;
            }
        }

        $existing->update([
            'is_active' => $model->is_active,
        ]);
    }

    public function existsByProviderAndToken(ProviderId $providerId, string $token): bool
    {
        return PaymentMethodModel::query()
            ->where('provider_id', $providerId->toString())
            ->where('token', $token)
            ->exists();
    }

    public function matching(PaymentMethodSearchCriteria $criteria): array
    {
        $models = $this->applyCriteria(PaymentMethodModel::query(), $criteria)
            ->orderByDesc('created_at')
            ->limit($criteria->limit)
            ->offset($criteria->offset)
            ->get();

        $methods = [];

        foreach ($models as $model) {
            $methods[] = $this->mapper->fromModel($model);
        }

        return $methods;
    }

    public function countMatching(PaymentMethodSearchCriteria $criteria): int
    {
        return $this->applyCriteria(PaymentMethodModel::query(), $criteria)->count();
    }

    /**
     * @param Builder<PaymentMethodModel> $query
     *
     * @return Builder<PaymentMethodModel>
     */
    private function applyCriteria(Builder $query, PaymentMethodSearchCriteria $criteria): Builder
    {
        if ($criteria->type instanceof \PayIn\Domain\PaymentMethod\PaymentMethodType) {
            $query->where('type', $criteria->type->value);
        }

        if ($criteria->providerId instanceof \PayIn\Domain\PaymentProvider\ProviderId) {
            $query->where('provider_id', $criteria->providerId->toString());
        }

        return $query;
    }

    private function isTokenViolation(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'token');
    }
}
