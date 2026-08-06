<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use PayIn\Domain\Client\ClientId;
use PayIn\Infrastructure\Persistence\Eloquent\Models\ClientModel;

/**
 * @extends Factory<ClientModel>
 */
final class ClientFactory extends Factory
{
    protected $model = ClientModel::class;

    public function definition(): array
    {
        return [
            'id' => ClientId::generate()->toString(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
        ];
    }
}
