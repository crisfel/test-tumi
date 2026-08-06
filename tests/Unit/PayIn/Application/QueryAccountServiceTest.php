<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Application;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PayIn\Application\Exception\AccountNotFoundException;
use PayIn\Application\UseCase\QueryAccountService;
use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Contracts\AccountRepository;
use PHPUnit\Framework\TestCase;
use Tests\Support\PayInFixtures;

final class QueryAccountServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private AccountRepository&MockInterface $accounts;

    protected function setUp(): void
    {
        $this->accounts = \Mockery::mock(AccountRepository::class);
    }

    public function test_finds_account_by_id(): void
    {
        $id = AccountId::generate();
        $account = PayInFixtures::account(\PayIn\Domain\Client\ClientId::generate(), id: $id);
        $this->accounts->shouldReceive('findById')->with($id)->andReturn($account);

        $this->assertSame($account, (new QueryAccountService($this->accounts))->findById($id));
    }

    public function test_throws_when_account_does_not_exist(): void
    {
        $id = AccountId::generate();
        $this->accounts->shouldReceive('findById')->with($id)->andReturnNull();

        $this->expectException(AccountNotFoundException::class);

        (new QueryAccountService($this->accounts))->findByIdOrFail($id);
    }
}
