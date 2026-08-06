<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Application;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PayIn\Application\Exception\PaymentProviderNotFoundException;
use PayIn\Application\UseCase\ListPaymentMethodsService;
use PayIn\Domain\Contracts\PaymentMethodRepository;
use PayIn\Domain\Contracts\PaymentMethodSearchCriteria;
use PayIn\Domain\Contracts\PaymentProviderRepository;
use PayIn\Domain\PaymentMethod\PaymentMethodType;
use PayIn\Domain\PaymentProvider\ProviderCode;
use PHPUnit\Framework\TestCase;
use Tests\Support\PayInFixtures;

final class ListPaymentMethodsServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private PaymentMethodRepository&MockInterface $paymentMethods;

    private PaymentProviderRepository&MockInterface $providers;

    protected function setUp(): void
    {
        $this->paymentMethods = \Mockery::mock(PaymentMethodRepository::class);
        $this->providers = \Mockery::mock(PaymentProviderRepository::class);
    }

    public function test_returns_paginated_methods(): void
    {
        $provider = PayInFixtures::provider();
        $method = PayInFixtures::method($provider->id());
        $criteria = new PaymentMethodSearchCriteria(limit: 10, offset: 0);

        $this->paymentMethods->shouldReceive('matching')->with($criteria)->andReturn([$method]);
        $this->paymentMethods->shouldReceive('countMatching')->with($criteria)->andReturn(1);

        $page = (new ListPaymentMethodsService($this->paymentMethods, $this->providers))->execute($criteria);

        $this->assertSame([$method], $page->items);
        $this->assertSame(1, $page->total);
        $this->assertSame(10, $page->limit);
    }

    public function test_resolves_provider_code_filter(): void
    {
        $provider = PayInFixtures::provider();
        $criteria = new PaymentMethodSearchCriteria(
            type: PaymentMethodType::CARD,
            providerCode: ProviderCode::fromString('fakepay'),
        );
        $resolved = new PaymentMethodSearchCriteria(
            type: PaymentMethodType::CARD,
            providerId: $provider->id(),
        );

        $this->providers->shouldReceive('findByCode')
            ->with(\Mockery::on(static fn (ProviderCode $code): bool => $code->equals($provider->code())))
            ->andReturn($provider);
        $this->paymentMethods->shouldReceive('matching')
            ->with(\Mockery::on(
                static fn (PaymentMethodSearchCriteria $c): bool =>
                $c->type === PaymentMethodType::CARD
                && $c->providerId instanceof \PayIn\Domain\PaymentProvider\ProviderId
                && $c->providerId->equals($provider->id())
            ))
            ->andReturn([]);
        $this->paymentMethods->shouldReceive('countMatching')->andReturn(0);

        $page = (new ListPaymentMethodsService($this->paymentMethods, $this->providers))->execute($criteria);

        $this->assertSame(0, $page->total);
    }

    public function test_throws_when_provider_filter_does_not_exist(): void
    {
        $this->providers->shouldReceive('findByCode')->andReturnNull();

        $this->expectException(PaymentProviderNotFoundException::class);

        (new ListPaymentMethodsService($this->paymentMethods, $this->providers))->execute(
            new PaymentMethodSearchCriteria(providerCode: ProviderCode::fromString('noprovider')),
        );
    }
}
