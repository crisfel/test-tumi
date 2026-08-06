<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Mappers;

use PayIn\Domain\PaymentMethod\PaymentMethodType;
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
        $model->supported_types = array_map(
            static fn (PaymentMethodType $type): string => $type->value,
            $provider->supportedTypes(),
        );
        $model->configuration = $provider->configuration();

        return $model;
    }

    public function fromModel(PaymentProviderModel $model): PaymentProvider
    {
        $supportedTypes = [];

        foreach ($model->supported_types ?? [] as $type) {
            if (is_string($type)) {
                $supportedTypes[] = PaymentMethodType::from($type);
            }
        }

        return PaymentProvider::reconstitute(
            ProviderId::fromString($model->id),
            ProviderCode::fromString($model->code),
            $model->name,
            (bool) $model->is_active,
            $supportedTypes,
            $model->configuration ?? [],
        );
    }
}
