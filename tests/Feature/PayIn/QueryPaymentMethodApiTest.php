<?php

declare(strict_types=1);

namespace Tests\Feature\PayIn;

use PayIn\Domain\PaymentMethod\PaymentMethodId;
use PayIn\Domain\PaymentMethod\PaymentMethodType;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\PaymentMethodMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentMethodRepository;
use Tests\Support\PayInFixtures;

final class QueryPaymentMethodApiTest extends PayInApiTestCase
{
    private EloquentPaymentMethodRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentPaymentMethodRepository(new PaymentMethodMapper());
    }

    public function test_returns_method_by_id(): void
    {
        $response = $this->getJson('/api/v1/payment-methods/' . $this->method->id()->toString());

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->method->id()->toString())
            ->assertJsonPath('data.provider_id', $this->provider->id()->toString())
            ->assertJsonPath('data.type', 'card')
            ->assertJsonPath('data.details_masked', '**** 4242')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_returns_404_when_method_does_not_exist(): void
    {
        $response = $this->getJson('/api/v1/payment-methods/' . PaymentMethodId::generate()->toString());

        $response->assertStatus(404)
            ->assertJsonPath('errors.0.code', 'PAYMENT_METHOD_NOT_FOUND');
    }

    public function test_lists_all_methods_with_pagination(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $method = PayInFixtures::method(
                $this->sandboxProvider->id(),
                token: 'tok_wallet_' . $i . '_' . uniqid(),
                type: PaymentMethodType::WALLET,
            );
            $this->repository->save($method);
        }

        $response = $this->getJson('/api/v1/payment-methods?limit=2&offset=0');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 6) // 3 del setUp + 3 creados
            ->assertJsonPath('meta.limit', 2)
            ->assertJsonPath('meta.offset', 0);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_lists_methods_filtered_by_type(): void
    {
        $response = $this->getJson('/api/v1/payment-methods?type=cash');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('cash', $response->json('data.0.type'));
    }

    public function test_lists_methods_filtered_by_provider_code(): void
    {
        $response = $this->getJson('/api/v1/payment-methods?provider_code=sandboxpay');

        $response->assertStatus(200);
        foreach ($response->json('data') as $method) {
            $this->assertSame($this->sandboxProvider->id()->toString(), $method['provider_id']);
        }
    }

    public function test_lists_methods_returns_404_for_unknown_provider(): void
    {
        $response = $this->getJson('/api/v1/payment-methods?provider_code=noprovider');

        $response->assertStatus(404)
            ->assertJsonPath('errors.0.code', 'PROVIDER_NOT_FOUND');
    }

    public function test_lists_methods_rejects_unknown_type(): void
    {
        $response = $this->getJson('/api/v1/payment-methods?type=cheque');

        $response->assertStatus(422);
    }
}
