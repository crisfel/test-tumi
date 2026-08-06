<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Application;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PayIn\Application\UseCase\ListPayInsService;
use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Contracts\PayInRepository;
use PayIn\Domain\Contracts\PayInSearchCriteria;
use PayIn\Domain\Currency;
use PayIn\Domain\Money;
use PayIn\Domain\PaymentMethod\PaymentMethodId;
use PHPUnit\Framework\TestCase;
use Tests\Support\PayInFixtures;

final class ListPayInsServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private PayInRepository&MockInterface $payIns;

    protected function setUp(): void
    {
        $this->payIns = \Mockery::mock(PayInRepository::class);
    }

    public function test_returns_paginated_page(): void
    {
        $items = [
            PayInFixtures::payIn(ClientId::generate(), AccountId::generate(), PaymentMethodId::generate(), Money::fromMinorUnits(1000, Currency::COP)),
            PayInFixtures::payIn(ClientId::generate(), AccountId::generate(), PaymentMethodId::generate(), Money::fromMinorUnits(2000, Currency::COP)),
        ];
        $criteria = new PayInSearchCriteria(limit: 10, offset: 20);

        $this->payIns->shouldReceive('matching')->with($criteria)->andReturn($items);
        $this->payIns->shouldReceive('countMatching')->with($criteria)->andReturn(42);

        $page = (new ListPayInsService($this->payIns))->execute($criteria);

        $this->assertSame($items, $page->items);
        $this->assertSame(42, $page->total);
        $this->assertSame(10, $page->limit);
        $this->assertSame(20, $page->offset);
    }
}
