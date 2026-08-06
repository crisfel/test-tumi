<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Application;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PayIn\Application\Command\RegisterPaymentMethodCommand;
use PayIn\Application\Exception\PaymentMethodAlreadyExistsException;
use PayIn\Application\Exception\PaymentProviderNotFoundException;
use PayIn\Application\Port\Clock;
use PayIn\Application\UseCase\RegisterPaymentMethodService;
use PayIn\Domain\Contracts\PaymentMethodRepository;
use PayIn\Domain\Contracts\PaymentProviderRepository;
use PayIn\Domain\Exceptions\PaymentMethodTypeNotSupportedException;
use PayIn\Domain\Exceptions\ProviderInactiveException;
use PayIn\Domain\PaymentMethod\PaymentMethodType;
use PayIn\Domain\PaymentProvider\PaymentProvider;
use PayIn\Domain\PaymentProvider\ProviderCode;
use PHPUnit\Framework\TestCase;
use Tests\Support\PayInFixtures;

final class RegisterPaymentMethodServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const NOW = '2026-01-01 10:00:00 UTC';

    private PaymentProviderRepository&MockInterface $providers;

    private PaymentMethodRepository&MockInterface $paymentMethods;

    protected function setUp(): void
    {
        $this->providers = \Mockery::mock(PaymentProviderRepository::class);
        $this->paymentMethods = \Mockery::mock(PaymentMethodRepository::class);
    }

    private function service(): RegisterPaymentMethodService
    {
        $clock = \Mockery::mock(Clock::class);
        $clock->shouldReceive('now')->andReturn(new \DateTimeImmutable(self::NOW));

        return new RegisterPaymentMethodService($this->providers, $this->paymentMethods, $clock);
    }

    private function command(string $token = 'tok_card_abc123'): RegisterPaymentMethodCommand
    {
        return new RegisterPaymentMethodCommand(
            providerCode: ProviderCode::fromString('fakepay'),
            type: PaymentMethodType::CARD,
            token: $token,
            detailsMasked: '**** 4242',
        );
    }

    private function expectProviderLookup(PaymentProvider $provider): void
    {
        $this->providers->shouldReceive('findByCode')
            ->with(\Mockery::on(static fn (ProviderCode $code): bool => $code->equals($provider->code())))
            ->andReturn($provider);
    }

    public function test_registers_an_active_payment_method(): void
    {
        $provider = PayInFixtures::provider();

        $this->expectProviderLookup($provider);
        $this->paymentMethods->shouldReceive('existsByProviderAndToken')
            ->with($provider->id(), 'tok_card_abc123')
            ->andReturn(false);
        $this->paymentMethods->shouldReceive('save')->once();

        $method = $this->service()->register($this->command());

        $this->assertTrue($method->isActive());
        $this->assertSame('**** 4242', $method->detailsMasked());
        $this->assertTrue($method->usesProvider($provider));
    }

    public function test_throws_when_provider_does_not_exist(): void
    {
        $this->providers->shouldReceive('findByCode')->andReturnNull();

        $this->expectException(PaymentProviderNotFoundException::class);

        $this->service()->register($this->command());
    }

    public function test_throws_when_provider_is_inactive(): void
    {
        $provider = PayInFixtures::provider(active: false);

        $this->expectProviderLookup($provider);

        $this->expectException(ProviderInactiveException::class);

        $this->service()->register($this->command());
    }

    public function test_throws_when_provider_does_not_support_method_type(): void
    {
        $provider = PayInFixtures::provider(
            supportedTypes: [PaymentMethodType::WALLET],
        );

        $this->expectProviderLookup($provider);

        $this->expectException(PaymentMethodTypeNotSupportedException::class);

        $this->service()->register($this->command());
    }

    public function test_throws_when_token_already_exists_in_provider(): void
    {
        $provider = PayInFixtures::provider();

        $this->expectProviderLookup($provider);
        $this->paymentMethods->shouldReceive('existsByProviderAndToken')
            ->with($provider->id(), 'tok_card_abc123')
            ->andReturn(true);
        $this->paymentMethods->shouldReceive('save')->never();

        $this->expectException(PaymentMethodAlreadyExistsException::class);

        $this->service()->register($this->command());
    }
}
