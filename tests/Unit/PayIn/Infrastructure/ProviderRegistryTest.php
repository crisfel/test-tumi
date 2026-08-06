<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Infrastructure;

use PayIn\Application\Exception\PaymentGatewayNotFoundException;
use PayIn\Application\Port\PaymentGateway;
use PayIn\Domain\PaymentProvider\PaymentProvider;
use PayIn\Domain\PaymentProvider\ProviderCode;
use PayIn\Domain\PaymentProvider\ProviderId;
use PayIn\Infrastructure\PaymentProviders\ProviderRegistry;
use PHPUnit\Framework\TestCase;
use Tests\Support\PayInFixtures;

final class ProviderRegistryTest extends TestCase
{
    public function test_resolves_registered_gateway_by_provider_code(): void
    {
        $gateway = $this->fakeGateway();
        $registry = new ProviderRegistry(['fakepay' => $gateway]);

        $provider = PaymentProvider::register(
            ProviderId::generate(),
            ProviderCode::fromString('fakepay'),
            'FakePay',
            true,
        );

        $this->assertSame($gateway, $registry->resolve($provider));
    }

    public function test_throws_when_no_gateway_is_registered_for_provider(): void
    {
        $registry = new ProviderRegistry([]);

        $this->expectException(PaymentGatewayNotFoundException::class);

        $registry->resolve(PayInFixtures::provider());
    }

    private function fakeGateway(): PaymentGateway
    {
        return new class () implements PaymentGateway {
            public function charge(\PayIn\Application\Dto\ChargeRequest $request): \PayIn\Application\Result\ChargeResult
            {
                return \PayIn\Application\Result\ChargeResult::success('TST-1');
            }
        };
    }
}
