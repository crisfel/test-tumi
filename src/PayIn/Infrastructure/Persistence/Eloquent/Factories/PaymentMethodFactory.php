<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use PayIn\Domain\PaymentMethod\PaymentMethodId;
use PayIn\Domain\PaymentMethod\PaymentMethodType;
use PayIn\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use PayIn\Infrastructure\Persistence\Eloquent\Models\PaymentMethodModel;
use PayIn\Infrastructure\Persistence\Eloquent\Models\PaymentProviderModel;

/**
 * @extends Factory<PaymentMethodModel>
 */
final class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethodModel::class;

    public function definition(): array
    {
        return [
            'id' => PaymentMethodId::generate()->toString(),
            'account_id' => AccountModel::factory(),
            'provider_id' => PaymentProviderModel::factory(),
            'type' => PaymentMethodType::CARD->value,
            'token' => 'tok_' . fake()->unique()->bothify('????????????'),
            'details_masked' => '**** ' . fake()->numerify('####'),
            'is_active' => true,
            'created_at' => now(),
        ];
    }
}
