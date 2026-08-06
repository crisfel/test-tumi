<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Repositories;

use PayIn\Domain\Contracts\PaymentProviderRepository;
use PayIn\Domain\PaymentProvider\PaymentProvider;
use PayIn\Domain\PaymentProvider\ProviderCode;
use PayIn\Domain\PaymentProvider\ProviderId;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\PaymentProviderMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Models\PaymentProviderModel;

/**
 * Implementación Eloquent del puerto PaymentProviderRepository.
 */
final readonly class EloquentPaymentProviderRepository implements PaymentProviderRepository
{
    public function __construct(private PaymentProviderMapper $mapper)
    {
    }

    public function findById(ProviderId $id): ?PaymentProvider
    {
        $model = PaymentProviderModel::query()->find($id->toString());

        return $model !== null ? $this->mapper->fromModel($model) : null;
    }

    public function findByCode(ProviderCode $code): ?PaymentProvider
    {
        $model = PaymentProviderModel::query()->where('code', $code->value())->first();

        return $model !== null ? $this->mapper->fromModel($model) : null;
    }

    public function all(): array
    {
        return PaymentProviderModel::query()
            ->orderBy('name')
            ->get()
            ->map(fn (PaymentProviderModel $model): PaymentProvider => $this->mapper->fromModel($model))
            ->all();
    }
}
