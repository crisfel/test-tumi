<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Application;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PayIn\Application\Exception\PaymentMethodNotFoundException;
use PayIn\Application\UseCase\QueryPaymentMethodService;
use PayIn\Domain\Contracts\PaymentMethodRepository;
use PayIn\Domain\PaymentMethod\PaymentMethodId;
use PHPUnit\Framework\TestCase;
use Tests\Support\PayInFixtures;

final class QueryPaymentMethodServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private PaymentMethodRepository&MockInterface $paymentMethods;

    protected function setUp(): void
    {
        $this->paymentMethods = \Mockery::mock(PaymentMethodRepository::class);
    }

    public function test_finds_method_by_id(): void
    {
        $id = PaymentMethodId::generate();
        $provider = PayInFixtures::provider();
        $method = PayInFixtures::method($provider->id(), id: $id);
        $this->paymentMethods->shouldReceive('findById')->with($id)->andReturn($method);

        $this->assertSame($method, (new QueryPaymentMethodService($this->paymentMethods))->findById($id));
    }

    public function test_throws_when_method_does_not_exist(): void
    {
        $id = PaymentMethodId::generate();
        $this->paymentMethods->shouldReceive('findById')->with($id)->andReturnNull();

        $this->expectException(PaymentMethodNotFoundException::class);

        (new QueryPaymentMethodService($this->paymentMethods))->findByIdOrFail($id);
    }
}
