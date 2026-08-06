<?php

declare(strict_types=1);

namespace Tests\Feature\PayIn;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PayIn\Domain\Account\Account;
use PayIn\Domain\Client\Client;
use PayIn\Domain\PaymentMethod\PaymentMethod;
use PayIn\Domain\PaymentMethod\PaymentMethodType;
use PayIn\Domain\PaymentProvider\PaymentProvider;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\AccountMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\ClientMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\PaymentMethodMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\PaymentProviderMapper;
use Tests\Support\PayInFixtures;
use Tests\TestCase;

abstract class PayInApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected Client $client;

    protected Account $account;

    protected Account $usdAccount;

    protected PaymentMethod $method;

    protected PaymentMethod $sandboxMethod;

    protected PaymentMethod $cashMethod;

    protected PaymentProvider $provider;

    protected PaymentProvider $sandboxProvider;

    protected PaymentProvider $cashProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = PayInFixtures::client();
        (new ClientMapper())->toModel($this->client)->save();

        $this->account = PayInFixtures::account($this->client->id());
        (new AccountMapper())->toModel($this->account)->save();

        $this->usdAccount = PayInFixtures::account($this->client->id(), \PayIn\Domain\Currency::USD);
        (new AccountMapper())->toModel($this->usdAccount)->save();

        $this->provider = PayInFixtures::provider();
        (new PaymentProviderMapper())->toModel($this->provider)->save();

        $this->sandboxProvider = \PayIn\Domain\PaymentProvider\PaymentProvider::register(
            \PayIn\Domain\PaymentProvider\ProviderId::generate(),
            \PayIn\Domain\PaymentProvider\ProviderCode::fromString('sandboxpay'),
            'SandboxPay',
            true,
            [PaymentMethodType::CARD, PaymentMethodType::BANK_TRANSFER, PaymentMethodType::WALLET, PaymentMethodType::PSE],
        );
        (new PaymentProviderMapper())->toModel($this->sandboxProvider)->save();

        $this->cashProvider = \PayIn\Domain\PaymentProvider\PaymentProvider::register(
            \PayIn\Domain\PaymentProvider\ProviderId::generate(),
            \PayIn\Domain\PaymentProvider\ProviderCode::fromString('cash'),
            'Efectivo',
            true,
            [PaymentMethodType::CASH],
        );
        (new PaymentProviderMapper())->toModel($this->cashProvider)->save();

        $this->method = PayInFixtures::method($this->provider->id());
        (new PaymentMethodMapper())->toModel($this->method)->save();

        $this->sandboxMethod = PayInFixtures::method(
            $this->sandboxProvider->id(),
            token: 'tok_wallet_usr_999',
            type: PaymentMethodType::WALLET,
        );
        (new PaymentMethodMapper())->toModel($this->sandboxMethod)->save();

        $this->cashMethod = PayInFixtures::method(
            $this->cashProvider->id(),
            token: 'tok_cash_0001',
            type: PaymentMethodType::CASH,
        );
        (new PaymentMethodMapper())->toModel($this->cashMethod)->save();
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'client_id' => $this->client->id()->toString(),
            'account_id' => $this->account->id()->toString(),
            'payment_method_id' => $this->method->id()->toString(),
            'amount' => 25000,
            'currency' => 'COP',
        ], $overrides);
    }
}
