<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Infrastructure;

use PayIn\Application\Dto\ChargeRequest;
use PayIn\Application\Result\ChargeOutcome;
use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Currency;
use PayIn\Domain\Money;
use PayIn\Domain\PayIn\TransactionId;
use PayIn\Domain\PaymentMethod\PaymentMethodId;
use PayIn\Domain\PaymentMethod\PaymentMethodType;
use PayIn\Domain\PaymentProvider\ProviderCode;
use PayIn\Infrastructure\PaymentProviders\FakePayProvider;
use PayIn\Infrastructure\PaymentProviders\ProviderBehavior;
use PHPUnit\Framework\TestCase;

final class FakePayProviderTest extends TestCase
{
    private ChargeRequest $request;

    protected function setUp(): void
    {
        $this->request = new ChargeRequest(
            payInId: TransactionId::generate(),
            clientId: ClientId::generate(),
            accountId: AccountId::generate(),
            paymentMethodId: PaymentMethodId::generate(),
            amount: Money::fromMinorUnits(25000, Currency::COP),
            reference: null,
            methodType: PaymentMethodType::CARD,
            methodToken: 'tok_card_abc123',
            providerCode: ProviderCode::fromString('fakepay'),
        );
    }

    public function test_success_behavior(): void
    {
        $result = (new FakePayProvider(new ProviderBehavior('success')))->charge($this->request);

        $this->assertSame(ChargeOutcome::SUCCESS, $result->outcome);
        $this->assertNotNull($result->providerTransactionId);
        $this->assertStringStartsWith('FP-', $result->providerTransactionId);
        $this->assertSame('approved', $result->payload['status']);
    }

    public function test_rejected_behavior(): void
    {
        $result = (new FakePayProvider(new ProviderBehavior('rejected')))->charge($this->request);

        $this->assertSame(ChargeOutcome::REJECTED, $result->outcome);
        $this->assertSame('PROVIDER_REJECTED', $result->errorCode);
        $this->assertNull($result->providerTransactionId);
    }

    public function test_timeout_behavior(): void
    {
        $result = (new FakePayProvider(new ProviderBehavior('timeout')))->charge($this->request);

        $this->assertSame(ChargeOutcome::TIMEOUT, $result->outcome);
        $this->assertNull($result->errorCode);
    }

    public function test_error_behavior(): void
    {
        $result = (new FakePayProvider(new ProviderBehavior('error')))->charge($this->request);

        $this->assertSame(ChargeOutcome::ERROR, $result->outcome);
        $this->assertSame('PROVIDER_ERROR', $result->errorCode);
    }

    public function test_rejects_invalid_behavior(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProviderBehavior('explode');
    }

    public function test_rejects_negative_latency(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProviderBehavior('success', -1);
    }

    public function test_simulates_latency(): void
    {
        $started = hrtime(true);
        (new FakePayProvider(new ProviderBehavior('success', 10)))->charge($this->request);
        $elapsedMs = (int) ((hrtime(true) - $started) / 1_000_000);

        $this->assertGreaterThanOrEqual(10, $elapsedMs);
    }
}
