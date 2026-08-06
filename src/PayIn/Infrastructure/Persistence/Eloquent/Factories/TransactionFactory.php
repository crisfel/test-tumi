<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use PayIn\Domain\Currency;
use PayIn\Domain\PayIn\PayInStatus;
use PayIn\Domain\PayIn\TransactionType;
use PayIn\Infrastructure\Persistence\Eloquent\Models\ClientModel;
use PayIn\Infrastructure\Persistence\Eloquent\Models\TransactionModel;

/**
 * @extends Factory<TransactionModel>
 */
final class TransactionFactory extends Factory
{
    protected $model = TransactionModel::class;

    public function definition(): array
    {
        return [
            'id' => \PayIn\Domain\PayIn\TransactionId::generate()->toString(),
            'type' => TransactionType::PAYIN->value,
            'client_id' => ClientModel::factory(),
            'amount' => fake()->numberBetween(1000, 1_000_000),
            'currency' => Currency::COP->value,
            'status' => PayInStatus::CREATED->value,
            'reference' => null,
            'provider_id' => null,
            'provider_transaction_id' => null,
            'provider_response' => null,
            'error_code' => null,
            'error_message' => null,
            'created_at' => now(),
            'processed_at' => null,
            'version' => 1,
        ];
    }

    public function status(PayInStatus $status): self
    {
        return $this->state(['status' => $status->value]);
    }

    public function withReference(string $reference): self
    {
        return $this->state(['reference' => $reference]);
    }
}
