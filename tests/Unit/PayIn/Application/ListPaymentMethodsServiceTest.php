<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Application;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PayIn\Application\Exception\AccountNotFoundException;
use PayIn\Application\UseCase\ListPaymentMethodsService;
use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Contracts\AccountRepository;
use PayIn\Domain\Contracts\PaymentMethodRepository;
use PayIn\Domain\Contracts\PaymentMethodSearchCriteria;
use PHPUnit\Framework\TestCase;
use Tests\Support\PayInFixtures;

final class ListPaymentMethodsServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private AccountRepository&MockInterface $accounts;

    private PaymentMethodRepository&MockInterface $paymentMethods;

    protected function setUp(): void
    {
        $this->accounts = \Mockery::mock(AccountRepository::class);
        $this->paymentMethods = \Mockery::mock(PaymentMethodRepository::class);
    }

    public function test_returns_paginated_methods_of_account(): void
    {
        $client = PayInFixtures::client();
        $account = PayInFixtures::account($client->id());
        $provider = PayInFixtures::provider();
        $method = PayInFixtures::method($account->id(), $provider->id());
        $criteria = new PaymentMethodSearchCriteria($account->id(), limit: 10, offset: 0);

        $this->accounts->shouldReceive('findById')->with($account->id())->andReturn($account);
        $this->paymentMethods->shouldReceive('matching')->with($criteria)->andReturn([$method]);
        $this->paymentMethods->shouldReceive('countMatching')->with($criteria)->andReturn(1);

        $page = (new ListPaymentMethodsService($this->accounts, $this->paymentMethods))->execute($criteria);

        $this->assertSame([$method], $page->items);
        $this->assertSame(1, $page->total);
        $this->assertSame(10, $page->limit);
    }

    public function test_throws_when_account_does_not_exist(): void
    {
        $this->accounts->shouldReceive('findById')->andReturnNull();

        $this->expectException(AccountNotFoundException::class);

        (new ListPaymentMethodsService($this->accounts, $this->paymentMethods))->execute(
            new PaymentMethodSearchCriteria(AccountId::generate()),
        );
    }
}
