<?php

declare(strict_types=1);

namespace Tests\Support;

use PayIn\Domain\Account\Account;
use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Client\Client;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Currency;
use PayIn\Domain\Email;
use PayIn\Domain\Money;
use PayIn\Domain\PayIn\PayIn;
use PayIn\Domain\PayIn\ProviderResponse;
use PayIn\Domain\PayIn\ProviderTransactionId;
use PayIn\Domain\PayIn\Reference;
use PayIn\Domain\PayIn\TransactionId;
use PayIn\Domain\PaymentMethod\PaymentMethod;
use PayIn\Domain\PaymentMethod\PaymentMethodId;
use PayIn\Domain\PaymentMethod\PaymentMethodType;
use PayIn\Domain\PaymentProvider\PaymentProvider;
use PayIn\Domain\PaymentProvider\ProviderCode;
use PayIn\Domain\PaymentProvider\ProviderId;

/**
 * Fábrica de agregados de dominio para pruebas.
 */
final class PayInFixtures
{
    public const NOW = '2026-01-01 10:00:00 UTC';

    public static function client(?ClientId $id = null, ?string $email = null): Client
    {
        return Client::register(
            $id ?? ClientId::generate(),
            'Ana García',
            Email::fromString($email ?? 'ana@example.com'),
        );
    }

    public static function account(ClientId $clientId, Currency $currency = Currency::COP, ?AccountId $id = null): Account
    {
        return Account::open($id ?? AccountId::generate(), $clientId, $currency);
    }

    public static function provider(
        ?ProviderId $id = null,
        bool $active = true,
        array $supportedTypes = [PaymentMethodType::CARD],
    ): PaymentProvider {
        return PaymentProvider::register(
            $id ?? ProviderId::generate(),
            ProviderCode::fromString('fakepay'),
            'FakePay',
            $active,
            $supportedTypes,
        );
    }

    public static function method(
        ProviderId $providerId,
        ?PaymentMethodId $id = null,
        bool $active = true,
        string $token = 'tok_card_abc123',
        PaymentMethodType $type = PaymentMethodType::CARD,
    ): PaymentMethod {
        return PaymentMethod::reconstitute(
            $id ?? PaymentMethodId::generate(),
            $providerId,
            $type,
            $token,
            '**** 4242',
            $active,
            new \DateTimeImmutable(self::NOW),
        );
    }

    public static function payIn(
        ClientId $clientId,
        AccountId $accountId,
        PaymentMethodId $paymentMethodId,
        Money $amount,
        ?Reference $reference = null,
        ?TransactionId $id = null,
    ): PayIn {
        return PayIn::create(
            id: $id ?? TransactionId::generate(),
            clientId: $clientId,
            accountId: $accountId,
            paymentMethodId: $paymentMethodId,
            amount: $amount,
            fees: Money::zero($amount->currency()),
            reference: $reference,
            createdAt: new \DateTimeImmutable(self::NOW),
        );
    }

    public static function processedPayIn(
        ClientId $clientId,
        AccountId $accountId,
        PaymentMethodId $paymentMethodId,
        Money $amount,
        ProviderId $providerId,
        ?TransactionId $id = null,
    ): PayIn {
        $payIn = self::payIn($clientId, $accountId, $paymentMethodId, $amount, null, $id);
        $payIn->markValidated();
        $payIn->markProcessing();
        $payIn->markProcessed(
            providerId: $providerId,
            providerTransactionId: ProviderTransactionId::fromString('FP-20260101-0001'),
            providerResponse: ProviderResponse::fromArray(['auth' => 'ABC']),
            processedAt: new \DateTimeImmutable(self::NOW),
        );

        return $payIn;
    }

    public static function failedPayIn(
        ClientId $clientId,
        AccountId $accountId,
        PaymentMethodId $paymentMethodId,
        Money $amount,
        ?TransactionId $id = null,
    ): PayIn {
        $payIn = self::payIn($clientId, $accountId, $paymentMethodId, $amount, null, $id);
        $payIn->markFailed('PROVIDER_REJECTED', 'Transacción rechazada.');

        return $payIn;
    }
}
