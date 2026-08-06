<?php

declare(strict_types=1);

namespace Tests\Feature\PayIn;

use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Currency;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\AccountMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Repositories\EloquentAccountRepository;
use Tests\Support\PayInFixtures;

final class QueryAccountApiTest extends PayInApiTestCase
{
    private EloquentAccountRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentAccountRepository(new AccountMapper());
    }

    public function test_returns_account_by_id(): void
    {
        $response = $this->getJson('/api/v1/accounts/' . $this->account->id()->toString());

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->account->id()->toString())
            ->assertJsonPath('data.client_id', $this->client->id()->toString())
            ->assertJsonPath('data.currency', 'COP')
            ->assertJsonPath('data.balance', 0);
    }

    public function test_returns_404_when_account_does_not_exist(): void
    {
        $response = $this->getJson('/api/v1/accounts/' . AccountId::generate()->toString());

        $response->assertStatus(404)
            ->assertJsonPath('errors.0.code', 'ACCOUNT_NOT_FOUND');
    }

    public function test_returns_404_for_invalid_uuid_in_path(): void
    {
        $response = $this->getJson('/api/v1/accounts/no-soy-uuid');

        $response->assertStatus(404);
    }

    public function test_lists_accounts_of_client_with_pagination(): void
    {
        $extraClient = PayInFixtures::client(email: 'otro.cliente@example.com');
        (new \PayIn\Infrastructure\Persistence\Eloquent\Mappers\ClientMapper())->toModel($extraClient)->save();

        for ($i = 0; $i < 3; $i++) {
            $account = PayInFixtures::account($extraClient->id(), Currency::fromCode(['COP', 'USD', 'MXN'][$i]));
            $this->repository->save($account);
        }

        // El cliente del setUp tiene COP + USD (2 cuentas); el extra tiene 3.
        $response = $this->getJson('/api/v1/accounts?client_id=' . $extraClient->id()->toString() . '&limit=2&offset=0');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.limit', 2)
            ->assertJsonPath('meta.offset', 0);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_lists_accounts_requires_client_id(): void
    {
        $response = $this->getJson('/api/v1/accounts');

        $response->assertStatus(422);
    }

    public function test_lists_accounts_returns_404_when_client_does_not_exist(): void
    {
        $response = $this->getJson('/api/v1/accounts?client_id=' . \PayIn\Domain\Client\ClientId::generate()->toString());

        $response->assertStatus(404)
            ->assertJsonPath('errors.0.code', 'CLIENT_NOT_FOUND');
    }
}
