<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Application;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PayIn\Application\Exception\ClientNotFoundException;
use PayIn\Application\UseCase\ListAccountsService;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Contracts\AccountRepository;
use PayIn\Domain\Contracts\AccountSearchCriteria;
use PayIn\Domain\Contracts\ClientRepository;
use PHPUnit\Framework\TestCase;
use Tests\Support\PayInFixtures;

final class ListAccountsServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ClientRepository&MockInterface $clients;

    private AccountRepository&MockInterface $accounts;

    protected function setUp(): void
    {
        $this->clients = \Mockery::mock(ClientRepository::class);
        $this->accounts = \Mockery::mock(AccountRepository::class);
    }

    public function test_returns_paginated_accounts_of_client(): void
    {
        $client = PayInFixtures::client();
        $account = PayInFixtures::account($client->id());
        $criteria = new AccountSearchCriteria($client->id(), limit: 10, offset: 0);

        $this->clients->shouldReceive('findById')->with($client->id())->andReturn($client);
        $this->accounts->shouldReceive('matching')->with($criteria)->andReturn([$account]);
        $this->accounts->shouldReceive('countMatching')->with($criteria)->andReturn(2);

        $page = (new ListAccountsService($this->clients, $this->accounts))->execute($criteria);

        $this->assertSame([$account], $page->items);
        $this->assertSame(2, $page->total);
        $this->assertSame(10, $page->limit);
        $this->assertSame(0, $page->offset);
    }

    public function test_throws_when_client_does_not_exist(): void
    {
        $this->clients->shouldReceive('findById')->andReturnNull();

        $this->expectException(ClientNotFoundException::class);

        (new ListAccountsService($this->clients, $this->accounts))->execute(
            new AccountSearchCriteria(ClientId::generate()),
        );
    }
}
