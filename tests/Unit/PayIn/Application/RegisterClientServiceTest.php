<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Application;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PayIn\Application\Command\RegisterClientCommand;
use PayIn\Application\Exception\ClientAlreadyExistsException;
use PayIn\Application\UseCase\RegisterClientService;
use PayIn\Domain\Contracts\ClientRepository;
use PayIn\Domain\Email;
use PHPUnit\Framework\TestCase;

final class RegisterClientServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ClientRepository&MockInterface $clients;

    protected function setUp(): void
    {
        $this->clients = \Mockery::mock(ClientRepository::class);
    }

    public function test_registers_a_new_client(): void
    {
        $email = Email::fromString('nuevo.cliente@example.com');

        $this->clients->shouldReceive('existsByEmail')->with($email)->andReturn(false);
        $this->clients->shouldReceive('save')->once();

        $client = (new RegisterClientService($this->clients))->register(
            new RegisterClientCommand('Nuevo Cliente', $email),
        );

        $this->assertSame('Nuevo Cliente', $client->name());
        $this->assertSame('nuevo.cliente@example.com', $client->email()->value());
        $this->assertNotNull($client->id()->toString());
    }

    public function test_throws_when_email_is_already_registered(): void
    {
        $email = Email::fromString('duplicado@example.com');

        $this->clients->shouldReceive('existsByEmail')->with($email)->andReturn(true);
        $this->clients->shouldReceive('save')->never();

        $this->expectException(ClientAlreadyExistsException::class);

        (new RegisterClientService($this->clients))->register(
            new RegisterClientCommand('Duplicado', $email),
        );
    }
}
