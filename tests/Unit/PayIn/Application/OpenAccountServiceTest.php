<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Application;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PayIn\Application\Command\OpenAccountCommand;
use PayIn\Application\Exception\AccountAlreadyExistsException;
use PayIn\Application\Exception\ClientNotFoundException;
use PayIn\Application\Port\Clock;
use PayIn\Application\UseCase\OpenAccountService;
use PayIn\Domain\Client\Client;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Contracts\AccountMovementRepository;
use PayIn\Domain\Contracts\AccountRepository;
use PayIn\Domain\Contracts\ClientRepository;
use PayIn\Domain\Currency;
use PayIn\Domain\Money;
use PHPUnit\Framework\TestCase;
use Tests\Support\PayInFixtures;

final class OpenAccountServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const NOW = '2026-01-01 10:00:00 UTC';

    private ClientRepository&MockInterface $clients;

    private AccountRepository&MockInterface $accounts;

    private AccountMovementRepository&MockInterface $movements;

    private Client $client;

    protected function setUp(): void
    {
        $this->clients = \Mockery::mock(ClientRepository::class);
        $this->accounts = \Mockery::mock(AccountRepository::class);
        $this->movements = \Mockery::mock(AccountMovementRepository::class);
        $this->client = PayInFixtures::client();
    }

    private function service(): OpenAccountService
    {
        $clock = \Mockery::mock(Clock::class);
        $clock->shouldReceive('now')->andReturn(new \DateTimeImmutable(self::NOW));

        return new OpenAccountService($this->clients, $this->accounts, $this->movements, $clock);
    }

    public function test_opens_an_account_with_zero_balance(): void
    {
        $this->clients->shouldReceive('findById')->with($this->client->id())->andReturn($this->client);
        $this->accounts->shouldReceive('existsByClientAndCurrency')
            ->with($this->client->id(), Currency::COP)
            ->andReturn(false);
        $this->accounts->shouldReceive('save')->once();
        $this->movements->shouldReceive('save')->never();

        $account = $this->service()->open(new OpenAccountCommand($this->client->id(), Currency::COP));

        $this->assertSame(Currency::COP, $account->currency());
        $this->assertTrue($account->balance()->isZero());
    }

    public function test_opens_an_account_with_initial_balance_and_records_movement(): void
    {
        $this->clients->shouldReceive('findById')->with($this->client->id())->andReturn($this->client);
        $this->accounts->shouldReceive('existsByClientAndCurrency')
            ->with($this->client->id(), Currency::COP)
            ->andReturn(false);
        $this->accounts->shouldReceive('save')->once();
        $this->movements->shouldReceive('save')->once();

        $account = $this->service()->open(new OpenAccountCommand(
            $this->client->id(),
            Currency::COP,
            Money::fromMinorUnits(10000, Currency::COP),
        ));

        $this->assertSame(10000, $account->balance()->minorUnits());
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
