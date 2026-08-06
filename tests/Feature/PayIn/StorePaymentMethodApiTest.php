<?php

declare(strict_types=1);

namespace Tests\Feature\PayIn;

use PayIn\Application\Exception\PaymentMethodAlreadyExistsException;
use PayIn\Domain\PaymentMethod\PaymentMethodId;
use PayIn\Domain\PaymentMethod\PaymentMethodType;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\PaymentMethodMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentMethodRepository;

final class StorePaymentMethodApiTest extends PayInApiTestCase
{
    public function test_registers_a_payment_method_successfully(): void
    {
        $response = $this->postJson('/api/v1/payment-methods', [
            'account_id' => $this->account->id()->toString(),
            'provider_code' => 'fakepay',
            'type' => 'card',
            'token' => 'tok_card_master_5555',
            'details_masked' => '**** 5555',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'account_id', 'provider_id', 'type', 'details_masked', 'is_active', 'created_at']])
            ->assertJsonPath('data.account_id', $this->account->id()->toString())
            ->assertJsonPath('data.provider_id', $this->provider->id()->toString())
            ->assertJsonPath('data.type', 'card')
            ->assertJsonPath('data.details_masked', '**** 5555')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('payment_methods', [
            'id' => $response->json('data.id'),
            'token' => 'tok_card_master_5555',
        ]);
    }

    public function test_token_is_never_exposed_in_response(): void
    {
        $response = $this->postJson('/api/v1/payment-methods', [
            'account_id' => $this->account->id()->toString(),
            'provider_code' => 'fakepay',
            'type' => 'card',
            'token' => 'tok_secret_token_9999',
            'details_masked' => '**** 9999',
        ]);

        $this->assertArrayNotHasKey('token', $response->json('data'));
        $this->assertStringNotContainsString('tok_secret_token_9999', $response->getContent());
    }

    public function test_returns_409_when_token_already_exists_in_account(): void
    {
        $this->postJson('/api/v1/payment-methods', [
            'account_id' => $this->account->id()->toString(),
            'provider_code' => 'fakepay',
            'type' => 'card',
            'token' => 'tok_card_visa_4242',
            'details_masked' => '**** 4242',
        ])->assertStatus(201);

        $response = $this->postJson('/api/v1/payment-methods', [
            'account_id' => $this->account->id()->toString(),
            'provider_code' => 'fakepay',
            'type' => 'card',
            'token' => 'tok_card_visa_4242',
            'details_masked' => '**** 4242',
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('errors.0.code', 'PAYMENT_METHOD_ALREADY_EXISTS');
    }

    public function test_returns_409_on_race_condition_via_database_unique(): void
    {
        // El setUp ya registró tok_card_abc123 en la cuenta; el UNIQUE
        // (account_id, token) de BD debe traducirse a la excepción.
        $repository = new EloquentPaymentMethodRepository(new PaymentMethodMapper());

        $this->expectException(PaymentMethodAlreadyExistsException::class);

        $duplicate = \PayIn\Domain\PaymentMethod\PaymentMethod::reconstitute(
            PaymentMethodId::generate(),
            $this->account->id(),
            $this->provider->id(),
            PaymentMethodType::CARD,
            'tok_card_abc123',
            '**** 4242',
            true,
            new \DateTimeImmutable('2026-01-01'),
        );

        $repository->save($duplicate);
    }

    public function test_returns_404_when_account_does_not_exist(): void
    {
        $response = $this->postJson('/api/v1/payment-methods', [
            'account_id' => \PayIn\Domain\Account\AccountId::generate()->toString(),
            'provider_code' => 'fakepay',
            'type' => 'card',
            'token' => 'tok_card_x_0001',
            'details_masked' => '**** 0001',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('errors.0.code', 'ACCOUNT_NOT_FOUND');
    }

    public function test_returns_404_when_provider_does_not_exist(): void
    {
        $response = $this->postJson('/api/v1/payment-methods', [
            'account_id' => $this->account->id()->toString(),
            'provider_code' => 'noprovider',
            'type' => 'card',
            'token' => 'tok_card_x_0002',
            'details_masked' => '**** 0002',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('errors.0.code', 'PROVIDER_NOT_FOUND');
    }

    public function test_returns_422_when_provider_is_inactive(): void
    {
        \PayIn\Infrastructure\Persistence\Eloquent\Models\PaymentProviderModel::query()
            ->whereKey($this->provider->id()->toString())
            ->update(['is_active' => false]);

        $response = $this->postJson('/api/v1/payment-methods', [
            'account_id' => $this->account->id()->toString(),
            'provider_code' => 'fakepay',
            'type' => 'card',
            'token' => 'tok_card_x_0003',
            'details_masked' => '**** 0003',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.0.code', 'PROVIDER_INACTIVE');
    }

    public function test_rejects_invalid_type(): void
    {
        $response = $this->postJson('/api/v1/payment-methods', [
            'account_id' => $this->account->id()->toString(),
            'provider_code' => 'fakepay',
            'type' => 'cheque',
            'token' => 'tok_card_x_0004',
            'details_masked' => '**** 0004',
        ]);

        $response->assertStatus(422);
    }

    public function test_rejects_invalid_provider_code(): void
    {
        $response = $this->postJson('/api/v1/payment-methods', [
            'account_id' => $this->account->id()->toString(),
            'provider_code' => 'FAKEPAY',
            'type' => 'card',
            'token' => 'tok_card_x_0005',
            'details_masked' => '**** 0005',
        ]);

        $response->assertStatus(422);
    }

    public function test_rejects_short_token(): void
    {
        $response = $this->postJson('/api/v1/payment-methods', [
            'account_id' => $this->account->id()->toString(),
            'provider_code' => 'fakepay',
            'type' => 'card',
            'token' => 'abc',
            'details_masked' => '**** 0006',
        ]);

        $response->assertStatus(422);
    }
}
