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
use PayIn\Infrastructure\PaymentProviders\CashGateway;
use PHPUnit\Framework\TestCase;

final class CashGatewayTest extends TestCase
{
    public function test_charge_returns_immediate_success(): void
    {
        $gateway = new CashGateway();

        $result = $gateway->charge(new ChargeRequest(
            payInId: TransactionId::generate(),
            clientId: ClientId::generate(),
            accountId: AccountId::generate(),
            paymentMethodId: PaymentMethodId::generate(),
            amount: Money::fromMinorUnits(5000, Currency::COP),
            reference: null,
            methodType: PaymentMethodType::CASH,
            methodToken: 'tok_cash_0001',
            providerCode: ProviderCode::fromString('cash'),
        ));

        $this->assertSame(ChargeOutcome::SUCCESS, $result->outcome);
        $this->assertStringStartsWith('CASH-', (string) $result->providerTransactionId);
        $this->assertSame('settled', $result->payload['status']);
        $this->assertSame(5000, $result->payload['amount']);
    }
}
