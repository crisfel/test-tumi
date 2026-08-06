<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Application;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PayIn\Application\Exception\PayInNotFoundException;
use PayIn\Application\UseCase\QueryPayInService;
use PayIn\Domain\Contracts\PayInRepository;
use PayIn\Domain\PayIn\TransactionId;
use PHPUnit\Framework\TestCase;
use Tests\Support\PayInFixtures;

final class QueryPayInServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private PayInRepository&MockInterface $payIns;

    protected function setUp(): void
    {
        $this->payIns = \Mockery::mock(PayInRepository::class);
    }

    public function test_finds_payin_by_id(): void
    {
        $id = TransactionId::generate();
        $payIn = PayInFixtures::payIn(
            \PayIn\Domain\Client\ClientId::generate(),
            \PayIn\Domain\Account\AccountId::generate(),
            \PayIn\Domain\PaymentMethod\PaymentMethodId::generate(),
            \PayIn\Domain\Money::fromMinorUnits(1000, \PayIn\Domain\Currency::COP),
            id: $id,
        );
        $this->payIns->shouldReceive('findById')->with($id)->andReturn($payIn);

        $this->assertSame($payIn, (new QueryPayInService($this->payIns))->findById($id));
    }

    public function test_returns_null_when_payin_does_not_exist(): void
    {
        $this->payIns->shouldReceive('findById')->andReturnNull();

        $this->assertNull((new QueryPayInService($this->payIns))->findById(TransactionId::generate()));
    }

    public function test_throws_when_payin_does_not_exist_with_or_fail(): void
    {
        $id = TransactionId::generate();
        $this->payIns->shouldReceive('findById')->with($id)->andReturnNull();

        $this->expectException(PayInNotFoundException::class);

        (new QueryPayInService($this->payIns))->findByIdOrFail($id);
    }
}
