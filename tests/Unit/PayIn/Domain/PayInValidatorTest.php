<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Domain;

use PayIn\Domain\Account\Account;
use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Client\Client;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Currency;
use PayIn\Domain\Email;
use PayIn\Domain\Exceptions\AccountNotBelongingToClientException;
use PayIn\Domain\Exceptions\CurrencyMismatchException;
use PayIn\Domain\Exceptions\InsufficientFundsException;
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

    private Account $originAccount;

    private Account $destinationAccount;

    private PaymentMethod $method;

    private PaymentProvider $provider;

    private PayInValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new PayInValidator();
        $clientId = ClientId::generate();
        $this->client = Client::register($clientId, 'Ana García', Email::fromString('ana@example.com'));
        $this->originAccount = Account::open(
            AccountId::generate(),
            $clientId,
            Currency::COP,
            Money::fromMinorUnits(100000, Currency::COP),
        );
        $this->destinationAccount = Account::open(AccountId::generate(), ClientId::generate(), Currency::COP);
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
            originAccountId: $this->originAccount->id(),
            accountId: $this->destinationAccount->id(),
            paymentMethodId: $this->method->id(),
            amount: $amount ?? Money::fromMinorUnits(25000, Currency::COP),
            fees: Money::zero(Currency::COP),
            reference: null,
            createdAt: new \DateTimeImmutable('2026-01-01'),
        );
    }

    public function test_valid_payin_passes_validation(): void
    {
        $this->validator->validate(
            $this->payIn(),
            $this->client,
            $this->originAccount,
            $this->destinationAccount,
            $this->method,
            $this->provider,
        );

        $this->addToAssertionCount(1);
    }

    public function test_rejects_non_positive_amount(): void
    {
        $this->expectException(PayInAmountInvalidException::class);

        $this->validator->validate(
            $this->payIn(Money::zero(Currency::COP)),
            $this->client,
            $this->originAccount,
            $this->destinationAccount,
            $this->method,
            $this->provider,
        );
    }

    public function test_rejects_origin_account_of_another_client(): void
    {
        $foreignOrigin = Account::open(
            AccountId::generate(),
            ClientId::generate(),
            Currency::COP,
            Money::fromMinorUnits(50000, Currency::COP),
        );
        $payIn = PayIn::create(
            id: TransactionId::generate(),
            clientId: $this->client->id(),
            originAccountId: $foreignOrigin->id(),
            accountId: $this->destinationAccount->id(),
            paymentMethodId: $this->method->id(),
            amount: Money::fromMinorUnits(1000, Currency::COP),
            fees: Money::zero(Currency::COP),
            reference: null,
            createdAt: new \DateTimeImmutable('2026-01-01'),
        );

        $this->expectException(AccountNotBelongingToClientException::class);

        $this->validator->validate(
            $payIn,
            $this->client,
            $foreignOrigin,
            $this->destinationAccount,
            $this->method,
            $this->provider,
        );
    }

    public function test_rejects_origin_currency_mismatch(): void
    {
        $usdOrigin = Account::open(AccountId::generate(), $this->client->id(), Currency::USD);

        $this->expectException(CurrencyMismatchException::class);

        $this->validator->validate(
            $this->payIn(),
            $this->client,
            $usdOrigin,
            $this->destinationAccount,
            $this->method,
            $this->provider,
        );
    }

    public function test_rejects_insufficient_funds_in_origin(): void
    {
        $poorOrigin = Account::open(
            AccountId::generate(),
            $this->client->id(),
            Currency::COP,
            Money::fromMinorUnits(5000, Currency::COP),
        );

        $this->expectException(InsufficientFundsException::class);

        $this->validator->validate(
            $this->payIn(),
            $this->client,
            $poorOrigin,
            $this->destinationAccount,
            $this->method,
            $this->provider,
        );
    }

    public function test_rejects_currency_mismatch_with_destination_account(): void
    {
        $usdDestination = Account::open(AccountId::generate(), ClientId::generate(), Currency::USD);

        $this->expectException(CurrencyMismatchException::class);

        $this->validator->validate(
            $this->payIn(),
            $this->client,
            $this->originAccount,
            $usdDestination,
            $this->method,
            $this->provider,
        );
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

        $this->validator->validate(
            $this->payIn(),
            $this->client,
            $this->originAccount,
            $this->destinationAccount,
            $inactive,
            $this->provider,
        );
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

        $this->validator->validate(
            $this->payIn(),
            $this->client,
            $this->originAccount,
            $this->destinationAccount,
            $this->method,
            $inactiveProvider,
        );
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
                originAccountId: $this->originAccount->id(),
                accountId: $this->destinationAccount->id(),
                paymentMethodId: $cashMethod->id(),
                amount: Money::fromMinorUnits(1000, Currency::COP),
                fees: Money::zero(Currency::COP),
                reference: null,
                createdAt: new \DateTimeImmutable('2026-01-01'),
            ),
            $this->client,
            $this->originAccount,
            $this->destinationAccount,
            $cashMethod,
            $this->provider,
        );
    }

    public function test_destination_account_may_belong_to_another_client(): void
    {
        $pedroAccount = Account::open(AccountId::generate(), ClientId::generate(), Currency::COP);
        $payIn = PayIn::create(
            id: TransactionId::generate(),
            clientId: $this->client->id(),
            originAccountId: $this->originAccount->id(),
            accountId: $pedroAccount->id(),
            paymentMethodId: $this->method->id(),
            amount: Money::fromMinorUnits(2000, Currency::COP),
            fees: Money::zero(Currency::COP),
            reference: null,
            createdAt: new \DateTimeImmutable('2026-01-01'),
        );

        $this->validator->validate(
            $payIn,
            $this->client,
            $this->originAccount,
            $pedroAccount,
            $this->method,
            $this->provider,
        );

        $this->addToAssertionCount(1);
    }
}
