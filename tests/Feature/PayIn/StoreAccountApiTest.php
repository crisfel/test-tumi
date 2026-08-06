<?php

declare(strict_types=1);

namespace Tests\Feature\PayIn;

use PayIn\Application\Exception\AccountAlreadyExistsException;
use PayIn\Domain\Currency;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\AccountMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Repositories\EloquentAccountRepository;

final class StoreAccountApiTest extends PayInApiTestCase
{
    public function test_opens_an_account_successfully(): void
    {
        $response = $this->postJson('/api/v1/accounts', [
            'client_id' => $this->client->id()->toString(),
            'currency' => 'EUR',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'client_id', 'currency', 'balance']])
            ->assertJsonPath('data.client_id', $this->client->id()->toString())
            ->assertJsonPath('data.currency', 'EUR')
            ->assertJsonPath('data.balance', 0);

        $this->assertDatabaseHas('accounts', [
            'id' => $response->json('data.id'),
            'client_id' => $this->client->id()->toString(),
            'currency' => 'EUR',
        ]);
    }

    public function test_returns_409_when_account_exists_for_currency(): void
    {
        $this->postJson('/api/v1/accounts', [
            'client_id' => $this->client->id()->toString(),
            'currency' => 'COP',
        ]);

        $response = $this->postJson('/api/v1/accounts', [
            'client_id' => $this->client->id()->toString(),
            'currency' => 'COP',
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('errors.0.code', 'ACCOUNT_ALREADY_EXISTS');
    }

    public function test_returns_409_on_race_condition_via_database_unique(): void
    {
        // El setUp ya creó la cuenta COP de ana; el UNIQUE (client_id,
        // currency) de BD debe traducirse a AccountAlreadyExistsException.
        $repository = new EloquentAccountRepository(new AccountMapper());

        $this->expectException(AccountAlreadyExistsException::class);

        $duplicate = \PayIn\Domain\Account\Account::open(
            \PayIn\Domain\Account\AccountId::generate(),
            $this->client->id(),
            Currency::COP,
        );

        $repository->save($duplicate);
    }

    public function test_allows_multiple_currencies_per_client(): void
    {
        // El setUp ya creó COP y USD; se validan monedas adicionales.
        $this->postJson('/api/v1/accounts', [
            'client_id' => $this->client->id()->toString(),
            'currency' => 'EUR',
        ])->assertStatus(201);

        $this->postJson('/api/v1/accounts', [
            'client_id' => $this->client->id()->toString(),
            'currency' => 'MXN',
        ])->assertStatus(201);
    }

    public function test_returns_404_when_client_does_not_exist(): void
    {
        $response = $this->postJson('/api/v1/accounts', [
            'client_id' => \PayIn\Domain\Client\ClientId::generate()->toString(),
            'currency' => 'COP',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('errors.0.code', 'CLIENT_NOT_FOUND');
    }

    public function test_rejects_invalid_currency(): void
    {
        $response = $this->postJson('/api/v1/accounts', [
            'client_id' => $this->client->id()->toString(),
            'currency' => 'BTC',
        ]);

        $response->assertStatus(422);
    }

    public function test_rejects_invalid_client_uuid(): void
    {
        $response = $this->postJson('/api/v1/accounts', [
            'client_id' => 'no-soy-uuid',
            'currency' => 'COP',
        ]);

        $response->assertStatus(422);
    }

    public function test_rejects_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/accounts', []);

        $response->assertStatus(422);
    }
}
