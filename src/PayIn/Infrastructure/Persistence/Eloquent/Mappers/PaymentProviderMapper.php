<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Mappers;

use PayIn\Domain\PaymentProvider\PaymentProvider;
use PayIn\Domain\PaymentProvider\ProviderCode;
use PayIn\Domain\PaymentProvider\ProviderId;
use PayIn\Infrastructure\Persistence\Eloquent\Models\PaymentProviderModel;

/**
 * Traduce entre el aggregate PaymentProvider y su representación persistente.
 */
final class PaymentProviderMapper
{
    public function toModel(PaymentProvider $provider): PaymentProviderModel
    {
        $model = new PaymentProviderModel();
        $model->id = $provider->id()->toString();
        $model->code = $provider->code()->value();
        $model->name = $provider->name();
        $model->is_active = $provider->isActive();
        $model->configuration = $provider->configuration();

        return $model;
    }

    public function fromModel(PaymentProviderModel $model): PaymentProvider
    {
        return PaymentProvider::reconstitute(
            ProviderId::fromString($model->id),
            ProviderCode::fromString($model->code),
            $model->name,
            (bool) $model->is_active,
            $model->configuration ?? [],
        );
    }
}
