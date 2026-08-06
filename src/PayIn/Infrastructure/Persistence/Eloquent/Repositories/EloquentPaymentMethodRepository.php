<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Repositories;

use PayIn\Domain\Contracts\PaymentMethodRepository;
use PayIn\Domain\PaymentMethod\PaymentMethod;
use PayIn\Domain\PaymentMethod\PaymentMethodId;
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
}
