<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Domain;

use PayIn\Domain\Account\Account;
use PayIn\Domain\Client\Client;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Currency;
use PayIn\Domain\Email;
use PayIn\Domain\Exceptions\CurrencyMismatchException;
use PayIn\Domain\Exceptions\PayInAmountInvalidException;
use PayIn\Domain\Exceptions\PaymentMethodInactiveException;
use PayIn\Domain\Exceptions\PaymentMethodTypeNotSupportedException;
use PayIn\Domain\Exceptions\ProviderInactiveException;
use PayIn\Domain\Money;
use PayIn\Domain\PayIn\PayIn;
use PayIn\Domain\PayIn\PayInValidator;
use PayIn\Domain\PayIn\TransactionId;
use PayIn\Domain\PaymentMethod\PaymentMethod;
use PayIn\Domain\PaymentMethod\PaymentMethodId;
use PayIn\Domain\PaymentMethod\PaymentMethodType;
use PayIn\Domain\PaymentProvider\PaymentProvider;
use PayIn\Domain\PaymentProvider\ProviderCode;
use PayIn\Domain\PaymentProvider\ProviderId;
use PHPUnit\Framework\TestCase;

final class PayInValidatorTest extends TestCase
{
    private Client $client;

    private Account $account;

    private PaymentMethod $method;

    private PaymentProvider $provider;

    private PayInValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new PayInValidator();
        $clientId = ClientId::generate();
        $this->client = Client::register($clientId, 'Ana García', Email::fromString('ana@example.com'));
        $this->account = Account::open(\PayIn\Domain\Account\AccountId::generate(), $clientId, Currency::COP);
        $this->provider = PaymentProvider::register(
            ProviderId::generate(),
            ProviderCode::fromString('fakepay'),
            'FakePay',
            true,
            [PaymentMethodType::CARD],
        );
        $this->method = PaymentMethod::register(
            PaymentMethodId::generate(),
            $this->provider->id(),
            PaymentMethodType::CARD,
            'tok_card_abc123',
            '**** 4242',
            new \DateTimeImmutable('2026-01-01'),
        );
    }

    private function payIn(?Money $amount = null): PayIn
    {
        return PayIn::create(
            id: TransactionId::generate(),
            clientId: $this->client->id(),
            accountId: $this->account->id(),
            paymentMethodId: $this->method->id(),
            amount: $amount ?? Money::fromMinorUnits(25000, Currency::COP),
            fees: Money::zero(Currency::COP),
            reference: null,
            createdAt: new \DateTimeImmutable('2026-01-01'),
        );
    }

    public function test_valid_payin_passes_validation(): void
    {
        $this->validator->validate($this->payIn(), $this->account, $this->method, $this->provider);

        $this->addToAssertionCount(1);
    }

    public function test_rejects_non_positive_amount(): void
    {
        $this->expectException(PayInAmountInvalidException::class);

        $this->validator->validate(
            $this->payIn(Money::zero(Currency::COP)),
            $this->account,
            $this->method,
            $this->provider,
        );
    }

    public function test_rejects_currency_mismatch_with_destination_account(): void
    {
        $usdAccount = Account::open($this->account->id(), $this->client->id(), Currency::USD);

        $this->expectException(CurrencyMismatchException::class);

        $this->validator->validate($this->payIn(), $usdAccount, $this->method, $this->provider);
    }

    public function test_rejects_inactive_method(): void
    {
        $inactive = PaymentMethod::reconstitute(
            $this->method->id(),
            $this->provider->id(),
            PaymentMethodType::CARD,
            'tok_card_abc123',
            '**** 4242',
            false,
            new \DateTimeImmutable('2026-01-01'),
        );

        $this->expectException(PaymentMethodInactiveException::class);

        $this->validator->validate($this->payIn(), $this->account, $inactive, $this->provider);
    }

    public function test_rejects_inactive_provider(): void
    {
        $inactiveProvider = PaymentProvider::reconstitute(
            $this->provider->id(),
            $this->provider->code(),
            'FakePay',
            false,
            [PaymentMethodType::CARD],
        );

        $this->expectException(ProviderInactiveException::class);

        $this->validator->validate($this->payIn(), $this->account, $this->method, $inactiveProvider);
    }

    public function test_rejects_method_type_not_supported_by_provider(): void
    {
        $cashProvider = PaymentProvider::register(
            ProviderId::generate(),
            ProviderCode::fromString('cash'),
            'Efectivo',
            true,
            [PaymentMethodType::CASH],
        );
        $cashMethod = PaymentMethod::register(
            PaymentMethodId::generate(),
            $cashProvider->id(),
            PaymentMethodType::CASH,
            'tok_cash_0001',
            'Efectivo',
            new \DateTimeImmutable('2026-01-01'),
        );

        $this->expectException(PaymentMethodTypeNotSupportedException::class);

        $this->validator->validate(
            PayIn::create(
                id: TransactionId::generate(),
                clientId: $this->client->id(),
                accountId: $this->account->id(),
                paymentMethodId: $cashMethod->id(),
                amount: Money::fromMinorUnits(1000, Currency::COP),
                fees: Money::zero(Currency::COP),
                reference: null,
                createdAt: new \DateTimeImmutable('2026-01-01'),
            ),
            $this->account,
            $cashMethod,
            $this->provider,
        );
    }

    public function test_destination_account_may_belong_to_another_client(): void
    {
        $pedroAccount = Account::open(\PayIn\Domain\Account\AccountId::generate(), ClientId::generate(), Currency::COP);
        $payIn = PayIn::create(
            id: TransactionId::generate(),
            clientId: $this->client->id(),
            accountId: $pedroAccount->id(),
            paymentMethodId: $this->method->id(),
            amount: Money::fromMinorUnits(50000, Currency::COP),
            fees: Money::zero(Currency::COP),
            reference: null,
            createdAt: new \DateTimeImmutable('2026-01-01'),
        );

        $this->validator->validate($payIn, $pedroAccount, $this->method, $this->provider);

        $this->addToAssertionCount(1);
    }
}
