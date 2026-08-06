<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Mappers;

use Illuminate\Support\Carbon;
use PayIn\Domain\PaymentMethod\PaymentMethod;
use PayIn\Domain\PaymentMethod\PaymentMethodId;
use PayIn\Domain\PaymentMethod\PaymentMethodType;
use PayIn\Domain\PaymentProvider\ProviderId;
use PayIn\Infrastructure\Persistence\Eloquent\Models\PaymentMethodModel;

/**
 * Traduce entre el aggregate PaymentMethod y su representación persistente.
 */
final class PaymentMethodMapper
{
    public function toModel(PaymentMethod $method): PaymentMethodModel
    {
        $model = new PaymentMethodModel();
        $model->id = $method->id()->toString();
        $model->provider_id = $method->providerId()->toString();
        $model->type = $method->type()->value;
        $model->token = $method->token();
        $model->details_masked = $method->detailsMasked();
        $model->is_active = $method->isActive();
        $model->created_at = Carbon::instance($method->createdAt());

        return $model;
    }

    public function fromModel(PaymentMethodModel $model): PaymentMethod
    {
        $createdAt = $model->created_at;

        if ($createdAt === null) {
            throw new \LogicException('payment_methods.created_at no puede ser nulo.');
        }

        return PaymentMethod::reconstitute(
            PaymentMethodId::fromString($model->id),
            ProviderId::fromString($model->provider_id),
            PaymentMethodType::from($model->type),
            $model->token,
            $model->details_masked,
            (bool) $model->is_active,
            new \DateTimeImmutable($createdAt->format('Y-m-d H:i:s.u')),
        );
    }
}
