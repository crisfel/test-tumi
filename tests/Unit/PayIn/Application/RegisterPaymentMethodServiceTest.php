<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Application;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PayIn\Application\Command\RegisterPaymentMethodCommand;
use PayIn\Application\Exception\AccountNotFoundException;
use PayIn\Application\Exception\PaymentMethodAlreadyExistsException;
use PayIn\Application\Exception\PaymentProviderNotFoundException;
use PayIn\Application\Port\Clock;
use PayIn\Application\UseCase\RegisterPaymentMethodService;
use PayIn\Domain\Account\Account;
use PayIn\Domain\Contracts\AccountRepository;
use PayIn\Domain\Contracts\PaymentMethodRepository;
use PayIn\Domain\Contracts\PaymentProviderRepository;
use PayIn\Domain\Exceptions\ProviderInactiveException;
use PayIn\Domain\PaymentMethod\PaymentMethodType;
use PayIn\Domain\PaymentProvider\ProviderCode;
use PHPUnit\Framework\TestCase;
use Tests\Support\PayInFixtures;

final class RegisterPaymentMethodServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const NOW = '2026-01-01 10:00:00 UTC';

    private AccountRepository&MockInterface $accounts;

    private PaymentProviderRepository&MockInterface $providers;

    private PaymentMethodRepository&MockInterface $paymentMethods;

    private Account $account;

    protected function setUp(): void
    {
        $this->accounts = \Mockery::mock(AccountRepository::class);
        $this->providers = \Mockery::mock(PaymentProviderRepository::class);
        $this->paymentMethods = \Mockery::mock(PaymentMethodRepository::class);
        $this->account = PayInFixtures::account(PayInFixtures::client()->id());
    }

    private function service(): RegisterPaymentMethodService
    {
        $clock = \Mockery::mock(Clock::class);
        $clock->shouldReceive('now')->andReturn(new \DateTimeImmutable(self::NOW));

        return new RegisterPaymentMethodService($this->accounts, $this->providers, $this->paymentMethods, $clock);
    }

    private function expectProviderLookup(\PayIn\Domain\PaymentProvider\PaymentProvider $provider): void
    {
        $this->providers->shouldReceive('findByCode')
            ->with(\Mockery::on(static fn (ProviderCode $code): bool => $code->equals($provider->code())))
            ->andReturn($provider);
    }

    private function command(string $token = 'tok_card_abc123'): RegisterPaymentMethodCommand
    {
        return new RegisterPaymentMethodCommand(
            accountId: $this->account->id(),
            providerCode: ProviderCode::fromString('fakepay'),
            type: PaymentMethodType::CARD,
            token: $token,
            detailsMasked: '**** 4242',
        );
    }

    public function test_registers_an_active_payment_method(): void
    {
        $provider = PayInFixtures::provider();

        $this->accounts->shouldReceive('findById')->with($this->account->id())->andReturn($this->account);
        $this->expectProviderLookup($provider);
        $this->paymentMethods->shouldReceive('existsByAccountAndToken')
            ->with($this->account->id(), 'tok_card_abc123')
            ->andReturn(false);
        $this->paymentMethods->shouldReceive('save')->once();

        $method = $this->service()->register($this->command());

        $this->assertTrue($method->isActive());
        $this->assertSame('**** 4242', $method->detailsMasked());
        $this->assertTrue($method->usesProvider($provider));
        $this->assertTrue($method->belongsToAccount($this->account));
    }

    public function test_throws_when_account_does_not_exist(): void
    {
        $this->accounts->shouldReceive('findById')->andReturnNull();

        $this->expectException(AccountNotFoundException::class);

        $this->service()->register($this->command());
    }

    public function test_throws_when_provider_does_not_exist(): void
    {
        $this->accounts->shouldReceive('findById')->with($this->account->id())->andReturn($this->account);
        $this->providers->shouldReceive('findByCode')->andReturnNull();

        $this->expectException(PaymentProviderNotFoundException::class);

        $this->service()->register($this->command());
    }

    public function test_throws_when_provider_is_inactive(): void
    {
        $provider = PayInFixtures::provider(active: false);

        $this->accounts->shouldReceive('findById')->with($this->account->id())->andReturn($this->account);
        $this->expectProviderLookup($provider);

        $this->expectException(ProviderInactiveException::class);

        $this->service()->register($this->command());
    }

    public function test_throws_when_token_already_exists_in_account(): void
    {
        $provider = PayInFixtures::provider();

        $this->accounts->shouldReceive('findById')->with($this->account->id())->andReturn($this->account);
        $this->expectProviderLookup($provider);
        $this->paymentMethods->shouldReceive('existsByAccountAndToken')
            ->with($this->account->id(), 'tok_card_abc123')
            ->andReturn(true);
        $this->paymentMethods->shouldReceive('save')->never();

        $this->expectException(PaymentMethodAlreadyExistsException::class);

        $this->service()->register($this->command());
    }
}
