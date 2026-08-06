<?php

declare(strict_types=1);

namespace Tests\Repositories\PayIn;

use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Currency;
use PayIn\Domain\Money;
use PayIn\Domain\PaymentProvider\PaymentProvider;
use PayIn\Domain\PaymentProvider\ProviderCode;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\AccountMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\ClientMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\PaymentMethodMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\PaymentProviderMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Repositories\EloquentAccountRepository;
use PayIn\Infrastructure\Persistence\Eloquent\Repositories\EloquentClientRepository;
use PayIn\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentMethodRepository;
use PayIn\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentProviderRepository;
use Tests\Support\PayInFixtures;

final class LookupRepositoriesTest extends RepositoryTestCase
{
    public function test_client_repository_round_trip(): void
    {
        $client = PayInFixtures::client();
        $model = (new ClientMapper())->toModel($client);
        $model->save();

        $reloaded = (new EloquentClientRepository(new ClientMapper()))->findById($client->id());

        $this->assertNotNull($reloaded);
        $this->assertSame($client->name(), $reloaded->name());
        $this->assertSame($client->email()->value(), $reloaded->email()->value());
    }

    public function test_client_repository_returns_null_when_not_found(): void
    {
        $this->assertNull((new EloquentClientRepository(new ClientMapper()))->findById(ClientId::generate()));
    }

    public function test_account_repository_saves_balance_updates(): void
    {
        $client = PayInFixtures::client();
        (new ClientMapper())->toModel($client)->save();

        $account = PayInFixtures::account($client->id());
        $repository = new EloquentAccountRepository(new AccountMapper());

        $repository->save($account);
        $account->credit(Money::fromMinorUnits(15000, Currency::COP));
        $repository->save($account);

        $reloaded = $repository->findById($account->id());

        $this->assertNotNull($reloaded);
        $this->assertSame(15000, $reloaded->balance()->minorUnits());
    }

    public function test_payment_provider_repository_lookup_by_id_and_code(): void
    {
        $provider = PaymentProvider::register(
            \PayIn\Domain\PaymentProvider\ProviderId::generate(),
            ProviderCode::fromString('fakepay'),
            'FakePay',
            true,
        );
        $model = (new PaymentProviderMapper())->toModel($provider);
        $model->save();

        $repository = new EloquentPaymentProviderRepository(new PaymentProviderMapper());

        $byId = $repository->findById($provider->id());
        $byCode = $repository->findByCode(ProviderCode::fromString('fakepay'));

        $this->assertNotNull($byId);
        $this->assertNotNull($byCode);
        $this->assertSame('fakepay', $byCode->code()->value());
        $this->assertTrue($byId->isActive());
    }

    public function test_payment_method_repository_round_trip(): void
    {
        $client = PayInFixtures::client();
        (new ClientMapper())->toModel($client)->save();
        $account = PayInFixtures::account($client->id());
        (new AccountMapper())->toModel($account)->save();
        $provider = PayInFixtures::provider();
        (new PaymentProviderMapper())->toModel($provider)->save();

        $method = PayInFixtures::method($provider->id());
        (new PaymentMethodMapper())->toModel($method)->save();

        $reloaded = (new EloquentPaymentMethodRepository(new PaymentMethodMapper()))->findById($method->id());

        $this->assertNotNull($reloaded);
        $this->assertSame($method->token(), $reloaded->token());
        $this->assertTrue($method->type() === $reloaded->type());
        $this->assertTrue($reloaded->isActive());
    }
}
