<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Application;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PayIn\Application\Command\OpenAccountCommand;
use PayIn\Application\Exception\AccountAlreadyExistsException;
use PayIn\Application\Exception\ClientNotFoundException;
use PayIn\Application\UseCase\OpenAccountService;
use PayIn\Domain\Client\Client;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Contracts\AccountRepository;
use PayIn\Domain\Contracts\ClientRepository;
use PayIn\Domain\Currency;
use PHPUnit\Framework\TestCase;
use Tests\Support\PayInFixtures;

final class OpenAccountServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ClientRepository&MockInterface $clients;

    private AccountRepository&MockInterface $accounts;

    private Client $client;

    protected function setUp(): void
    {
        $this->clients = \Mockery::mock(ClientRepository::class);
        $this->accounts = \Mockery::mock(AccountRepository::class);
        $this->client = PayInFixtures::client();
    }

    private function service(): OpenAccountService
    {
        return new OpenAccountService($this->clients, $this->accounts);
    }

    public function test_opens_an_account_with_zero_balance(): void
    {
        $this->clients->shouldReceive('findById')->with($this->client->id())->andReturn($this->client);
        $this->accounts->shouldReceive('existsByClientAndCurrency')
            ->with($this->client->id(), Currency::COP)
            ->andReturn(false);
        $this->accounts->shouldReceive('save')->once();

        $account = $this->service()->open(
            new OpenAccountCommand($this->client->id(), Currency::COP),
        );

        $this->assertSame(Currency::COP, $account->currency());
        $this->assertTrue($account->balance()->isZero());
        $this->assertTrue($account->clientId()->equals($this->client->id()));
    }

    public function test_throws_when_client_does_not_exist(): void
    {
        $this->clients->shouldReceive('findById')->andReturnNull();

        $this->expectException(ClientNotFoundException::class);

        $this->service()->open(new OpenAccountCommand(ClientId::generate(), Currency::USD));
    }

    public function test_throws_when_account_already_exists_for_currency(): void
    {
        $this->clients->shouldReceive('findById')->with($this->client->id())->andReturn($this->client);
        $this->accounts->shouldReceive('existsByClientAndCurrency')
            ->with($this->client->id(), Currency::USD)
            ->andReturn(true);
        $this->accounts->shouldReceive('save')->never();

        $this->expectException(AccountAlreadyExistsException::class);

        $this->service()->open(new OpenAccountCommand($this->client->id(), Currency::USD));
    }
}
