<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Domain;

use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Currency;
use PayIn\Domain\Exceptions\InvalidStateTransitionException;
use PayIn\Domain\Money;
use PayIn\Domain\PayIn\Events\PayInCreated;
use PayIn\Domain\PayIn\Events\PayInFailed;
use PayIn\Domain\PayIn\Events\PayInProcessed;
use PayIn\Domain\PayIn\Events\PayInProcessing;
use PayIn\Domain\PayIn\Events\PayInValidated;
use PayIn\Domain\PayIn\PayIn;
use PayIn\Domain\PayIn\PayInStatus;
use PayIn\Domain\PayIn\ProviderResponse;
use PayIn\Domain\PayIn\ProviderTransactionId;
use PayIn\Domain\PayIn\Reference;
use PayIn\Domain\PayIn\TransactionId;
use PayIn\Domain\PaymentMethod\PaymentMethodId;
use PayIn\Domain\PaymentProvider\ProviderId;
use PHPUnit\Framework\TestCase;

final class PayInTest extends TestCase
{
    private const DATE = '2026-01-01 10:00:00 UTC';

    private function createPayIn(?Reference $reference = null): PayIn
    {
        return PayIn::create(
            id: TransactionId::generate(),
            clientId: ClientId::generate(),
            originAccountId: AccountId::generate(),
            accountId: AccountId::generate(),
            paymentMethodId: PaymentMethodId::generate(),
            amount: Money::fromMinorUnits(25000, Currency::COP),
            fees: Money::zero(Currency::COP),
            reference: $reference,
            createdAt: new \DateTimeImmutable(self::DATE),
        );
    }

    public function test_created_payin_starts_in_created_status(): void
    {
        $payIn = $this->createPayIn();

        $this->assertSame(PayInStatus::CREATED, $payIn->status());
        $this->assertNull($payIn->errorCode());
        $this->assertNull($payIn->providerTransactionId());
    }

    public function test_create_records_pay_in_created_event(): void
    {
        $payIn = $this->createPayIn();

        $events = $payIn->releaseEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(PayInCreated::class, $events[0]);
        $this->assertSame($payIn->id(), $events[0]->payInId);
        $this->assertSame([], $payIn->releaseEvents(), 'Los eventos deben liberarse solo una vez.');
    }

    public function test_full_happy_path_lifecycle(): void
    {
        $payIn = $this->createPayIn(Reference::fromString('order-0001'));
        $providerId = ProviderId::generate();
        $providerTxId = ProviderTransactionId::fromString('FP-20260101-0001');

        $payIn->markValidated();
        $payIn->markProcessing();
        $payIn->markProcessed(
            $providerId,
            $providerTxId,
            ProviderResponse::fromArray(['status' => 'approved', 'auth' => 'ABC123']),
            new \DateTimeImmutable(self::DATE),
        );

        $this->assertSame(PayInStatus::PROCESSED, $payIn->status());
        $this->assertTrue($payIn->status()->isTerminal());
        $this->assertSame($providerId, $payIn->providerId());
        $this->assertSame('FP-20260101-0001', $payIn->providerTransactionId()->value());
        $this->assertSame('ABC123', $payIn->providerResponse()->toArray()['auth']);
        $this->assertNull($payIn->errorCode());

        $events = $payIn->releaseEvents();
        $this->assertCount(4, $events);
        $this->assertInstanceOf(PayInCreated::class, $events[0]);
        $this->assertInstanceOf(PayInValidated::class, $events[1]);
        $this->assertInstanceOf(PayInProcessing::class, $events[2]);
        $this->assertInstanceOf(PayInProcessed::class, $events[3]);
        $this->assertSame($providerTxId, $events[3]->providerTransactionId);
    }

    public function test_mark_failed_from_created(): void
    {
        $payIn = $this->createPayIn();
        $payIn->markFailed('PROVIDER_REJECTED', 'Transacción rechazada por el emisor.');

        $this->assertSame(PayInStatus::FAILED, $payIn->status());
        $this->assertSame('PROVIDER_REJECTED', $payIn->errorCode());
        $this->assertSame('Transacción rechazada por el emisor.', $payIn->errorMessage());
        $this->assertNotNull($payIn->processedAt());

        $events = $payIn->releaseEvents();
        $this->assertInstanceOf(PayInFailed::class, $events[1]);
        $this->assertSame('PROVIDER_REJECTED', $events[1]->errorCode);
    }

    public function test_cannot_skip_from_created_to_processed(): void
    {
        $payIn = $this->createPayIn();

        $this->expectException(InvalidStateTransitionException::class);

        $payIn->markProcessed(
            ProviderId::generate(),
            ProviderTransactionId::fromString('FP-1'),
            ProviderResponse::empty(),
            new \DateTimeImmutable(self::DATE),
        );
    }

    public function test_cannot_validate_a_failed_payin(): void
    {
        $payIn = $this->createPayIn();
        $payIn->markFailed('PROVIDER_TIMEOUT', 'Timeout');

        $this->expectException(InvalidStateTransitionException::class);

        $payIn->markValidated();
    }

    public function test_cannot_fail_a_failed_payin(): void
    {
        $payIn = $this->createPayIn();
        $payIn->markFailed('PROVIDER_TIMEOUT', 'Timeout');

        $this->expectException(InvalidStateTransitionException::class);

        $payIn->markFailed('PROVIDER_ERROR', 'Error');
    }

    public function test_cannot_process_a_validated_payin_without_processing_state(): void
    {
        $payIn = $this->createPayIn();
        $payIn->markValidated();

        $this->expectException(InvalidStateTransitionException::class);

        $payIn->markProcessed(
            ProviderId::generate(),
            ProviderTransactionId::fromString('FP-1'),
            ProviderResponse::empty(),
            new \DateTimeImmutable(self::DATE),
        );
    }

    public function test_reconstituted_payin_does_not_emit_events(): void
    {
        $id = TransactionId::generate();

        $payIn = PayIn::reconstitute(
            id: $id,
            clientId: ClientId::generate(),
            originAccountId: AccountId::generate(),
            accountId: AccountId::generate(),
            paymentMethodId: PaymentMethodId::generate(),
            amount: Money::fromMinorUnits(1000, Currency::USD),
            fees: Money::zero(Currency::USD),
            type: \PayIn\Domain\PayIn\TransactionType::PAYIN,
            createdAt: new \DateTimeImmutable(self::DATE),
            status: PayInStatus::PROCESSED,
            reference: null,
            providerId: ProviderId::generate(),
            providerTransactionId: ProviderTransactionId::fromString('FP-9'),
            providerResponse: ProviderResponse::empty(),
            errorCode: null,
            errorMessage: null,
            processedAt: new \DateTimeImmutable(self::DATE),
        );

        $this->assertSame(PayInStatus::PROCESSED, $payIn->status());
        $this->assertSame([], $payIn->releaseEvents());
        $this->assertSame($id, $payIn->id());
    }

    public function test_aggregate_exposes_financial_data(): void
    {
        $payIn = $this->createPayIn();

        $this->assertSame(25000, $payIn->amount()->minorUnits());
        $this->assertSame(Currency::COP, $payIn->amount()->currency());
        $this->assertTrue($payIn->fees()->isZero());
        $this->assertNotNull($payIn->originAccountId()->toString());
    }
}
