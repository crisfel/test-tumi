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
use PayIn\Infrastructure\PaymentProviders\ProviderBehavior;
use PayIn\Infrastructure\PaymentProviders\SandboxPayProvider;
use PHPUnit\Framework\TestCase;

final class SandboxPayProviderTest extends TestCase
{
    private ChargeRequest $request;

    protected function setUp(): void
    {
        $this->request = new ChargeRequest(
            payInId: TransactionId::generate(),
            clientId: ClientId::generate(),
            accountId: AccountId::generate(),
            paymentMethodId: PaymentMethodId::generate(),
            amount: Money::fromMinorUnits(1000, Currency::USD),
            reference: \PayIn\Domain\PayIn\Reference::fromString('order-42'),
            methodType: PaymentMethodType::WALLET,
            methodToken: 'tok_wallet_1',
            providerCode: ProviderCode::fromString('sandboxpay'),
        );
    }

    public function test_success_behavior(): void
    {
        $result = (new SandboxPayProvider(new ProviderBehavior('success')))->charge($this->request);

        $this->assertSame(ChargeOutcome::SUCCESS, $result->outcome);
        $this->assertStringStartsWith('SP-', $result->providerTransactionId);
        $this->assertSame('success', $result->payload['result']);
    }

    public function test_rejected_behavior_uses_provider_specific_code(): void
    {
        $result = (new SandboxPayProvider(new ProviderBehavior('rejected')))->charge($this->request);

        $this->assertSame(ChargeOutcome::REJECTED, $result->outcome);
        $this->assertSame('SP_REJECTED_FUNDS', $result->errorCode);
    }

    public function test_timeout_behavior(): void
    {
        $result = (new SandboxPayProvider(new ProviderBehavior('timeout')))->charge($this->request);

        $this->assertSame(ChargeOutcome::TIMEOUT, $result->outcome);
        $this->assertNull($result->providerTransactionId);
    }

    public function test_error_behavior(): void
    {
        $result = (new SandboxPayProvider(new ProviderBehavior('error')))->charge($this->request);

        $this->assertSame(ChargeOutcome::ERROR, $result->outcome);
        $this->assertSame('SP_INTERNAL_ERROR', $result->errorCode);
    }

    public function test_same_contract_yields_normalized_result(): void
    {
        $fake = (new \PayIn\Infrastructure\PaymentProviders\FakePayProvider(new ProviderBehavior('success')))->charge($this->request);
        $sandbox = (new SandboxPayProvider(new ProviderBehavior('success')))->charge($this->request);

        $this->assertSame($fake->outcome, $sandbox->outcome, 'Ambos proveedores cumplen el mismo contrato (LSP).');
    }
}
