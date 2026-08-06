<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use PayIn\Domain\PaymentProvider\ProviderId;
use PayIn\Infrastructure\Persistence\Eloquent\Models\PaymentProviderModel;

/**
 * @extends Factory<PaymentProviderModel>
 */
final class PaymentProviderFactory extends Factory
{
    protected $model = PaymentProviderModel::class;

    public function definition(): array
    {
        return [
            'id' => ProviderId::generate()->toString(),
            'code' => fake()->unique()->regexify('[a-z]{4,10}'),
            'name' => fake()->company(),
            'is_active' => true,
            'configuration' => [],
        ];
    }
}
