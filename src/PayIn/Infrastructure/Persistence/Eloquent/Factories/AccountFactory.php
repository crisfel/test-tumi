<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Currency;
use PayIn\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use PayIn\Infrastructure\Persistence\Eloquent\Models\ClientModel;

/**
 * @extends Factory<AccountModel>
 */
final class AccountFactory extends Factory
{
    protected $model = AccountModel::class;

    public function definition(): array
    {
        return [
            'id' => AccountId::generate()->toString(),
            'client_id' => ClientModel::factory(),
            'currency' => Currency::COP->value,
            'balance' => 0,
        ];
    }

    public function currency(Currency $currency): self
    {
        return $this->state(['currency' => $currency->value]);
    }
}
