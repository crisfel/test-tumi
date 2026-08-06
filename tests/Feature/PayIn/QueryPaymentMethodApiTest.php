<?php

declare(strict_types=1);

namespace Tests\Feature\PayIn;

use PayIn\Domain\PaymentMethod\PaymentMethodId;
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

    public function test_lists_methods_of_account_with_pagination(): void
    {
        $otherClient = PayInFixtures::client(email: 'metodos.cliente@example.com');
        (new \PayIn\Infrastructure\Persistence\Eloquent\Mappers\ClientMapper())->toModel($otherClient)->save();
        $otherAccount = PayInFixtures::account($otherClient->id(), \PayIn\Domain\Currency::USD);
        (new \PayIn\Infrastructure\Persistence\Eloquent\Mappers\AccountMapper())->toModel($otherAccount)->save();

        for ($i = 0; $i < 3; $i++) {
            $method = PayInFixtures::method(
                $otherAccount->id(),
                $this->sandboxProvider->id(),
                token: 'tok_wallet_' . $i . '_' . uniqid(),
            );
            $this->repository->save($method);
        }

        $response = $this->getJson('/api/v1/payment-methods?account_id=' . $otherAccount->id()->toString() . '&limit=2&offset=0');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.limit', 2)
            ->assertJsonPath('meta.offset', 0);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_lists_methods_requires_account_id(): void
    {
        $response = $this->getJson('/api/v1/payment-methods');

        $response->assertStatus(422);
    }

    public function test_lists_methods_returns_404_when_account_does_not_exist(): void
    {
        $response = $this->getJson('/api/v1/payment-methods?account_id=' . \PayIn\Domain\Account\AccountId::generate()->toString());

        $response->assertStatus(404)
            ->assertJsonPath('errors.0.code', 'ACCOUNT_NOT_FOUND');
    }
}
