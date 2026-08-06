<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Application;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PayIn\Application\Command\ProcessPayInCommand;
use PayIn\Application\Exception\AccountNotFoundException;
use PayIn\Application\Exception\ClientNotFoundException;
use PayIn\Application\Exception\PayInProcessingException;
use PayIn\Application\Exception\PaymentMethodNotFoundException;
use PayIn\Application\Exception\PaymentProviderNotFoundException;
use PayIn\Application\Exception\ReferenceAlreadyUsedException;
use PayIn\Application\Port\Clock;
use PayIn\Application\Port\EventBus;
use PayIn\Application\Port\Logger;
use PayIn\Application\Port\PaymentGateway;
use PayIn\Application\Port\PaymentGatewayRegistry;
use PayIn\Application\Port\TransactionManager;
use PayIn\Application\Result\ChargeResult;
use PayIn\Application\UseCase\ProcessPayInService;
use PayIn\Domain\Account\Account;
use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Client\Client;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Contracts\AccountRepository;
use PayIn\Domain\Contracts\ClientRepository;
use PayIn\Domain\Contracts\PayInRepository;
use PayIn\Domain\Contracts\PaymentMethodRepository;
use PayIn\Domain\Contracts\PaymentProviderRepository;
use PayIn\Domain\Currency;
use PayIn\Domain\Email;
use PayIn\Domain\Exceptions\PaymentMethodInactiveException;
use PayIn\Domain\Money;
use PayIn\Domain\PayIn\PayInStatus;
use PayIn\Domain\PayIn\PayInValidator;
use PayIn\Domain\PayIn\Reference;
use PayIn\Domain\PaymentMethod\PaymentMethod;
use PayIn\Domain\PaymentMethod\PaymentMethodId;
use PayIn\Domain\PaymentMethod\PaymentMethodType;
use PayIn\Domain\PaymentProvider\PaymentProvider;
use PayIn\Domain\PaymentProvider\ProviderCode;
use PayIn\Domain\PaymentProvider\ProviderId;
use PHPUnit\Framework\TestCase;

final class ProcessPayInServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const NOW = '2026-01-01 10:00:00 UTC';

    private Client $client;

    private Account $account;

    private PaymentMethod $method;

    private PaymentProvider $provider;

    private ClientRepository&MockInterface $clients;

    private AccountRepository&MockInterface $accounts;

    private PaymentMethodRepository&MockInterface $paymentMethods;

    private PaymentProviderRepository&MockInterface $providers;

    private PayInRepository&MockInterface $payIns;

    private PaymentGatewayRegistry&MockInterface $gateways;

    private PaymentGateway&MockInterface $gateway;

    protected function setUp(): void
    {
        $clientId = ClientId::generate();
        $this->client = Client::register($clientId, 'Ana García', Email::fromString('ana@example.com'));
        $this->account = Account::open(AccountId::generate(), $clientId, Currency::COP);
        $this->provider = PaymentProvider::register(
            ProviderId::generate(),
            ProviderCode::fromString('fakepay'),
            'FakePay',
            true,
        );
        $this->method = PaymentMethod::register(
            PaymentMethodId::generate(),
            $this->account->id(),
            $this->provider->id(),
            PaymentMethodType::CARD,
            'tok_card_abc123',
            '**** 4242',
            new \DateTimeImmutable(self::NOW),
        );

        $this->clients = \Mockery::mock(ClientRepository::class);
        $this->accounts = \Mockery::mock(AccountRepository::class);
        $this->paymentMethods = \Mockery::mock(PaymentMethodRepository::class);
        $this->providers = \Mockery::mock(PaymentProviderRepository::class);
        $this->payIns = \Mockery::mock(PayInRepository::class);
        $this->gateways = \Mockery::mock(PaymentGatewayRegistry::class);
        $this->gateway = \Mockery::mock(PaymentGateway::class);
    }

    private function service(): ProcessPayInService
    {
        $clock = \Mockery::mock(Clock::class);
        $clock->shouldReceive('now')->andReturn(new \DateTimeImmutable(self::NOW));
        $events = \Mockery::mock(EventBus::class);
        $events->shouldReceive('dispatch');
        $logger = \Mockery::mock(Logger::class);
        $logger->shouldReceive('info', 'error', 'warning');
        $transactions = \Mockery::mock(TransactionManager::class);
        $transactions->shouldReceive('execute')->andReturnUsing(static fn (callable $callback): mixed => $callback());

        return new ProcessPayInService(
            clients: $this->clients,
            accounts: $this->accounts,
            paymentMethods: $this->paymentMethods,
            providers: $this->providers,
            payIns: $this->payIns,
            validator: new PayInValidator(),
            gateways: $this->gateways,
            transactions: $transactions,
            events: $events,
            clock: $clock,
            logger: $logger,
        );
    }

    private function command(?Reference $reference = null): ProcessPayInCommand
    {
        return new ProcessPayInCommand(
            clientId: $this->client->id(),
            accountId: $this->account->id(),
            paymentMethodId: $this->method->id(),
            amount: Money::fromMinorUnits(25000, Currency::COP),
            reference: $reference,
        );
    }

    private function expectLoads(): void
    {
        $this->clients->shouldReceive('findById')->andReturn($this->client);
        $this->accounts->shouldReceive('findById')->andReturn($this->account);
        $this->paymentMethods->shouldReceive('findById')->andReturn($this->method);
        $this->providers->shouldReceive('findById')->andReturn($this->provider);
    }

    private function expectGatewayResolved(ChargeResult $result): void
    {
        $this->gateways->shouldReceive('resolve')->andReturn($this->gateway);
        $this->gateway->shouldReceive('charge')->andReturn($result);
    }

    public function test_processes_payin_successfully(): void
    {
        $this->expectLoads();
        $this->expectGatewayResolved(ChargeResult::success('FP-20260101-0001', 'approved', ['auth' => 'ABC']));
        $this->payIns->shouldReceive('save');
        $savedAccount = null;
        $this->accounts->shouldReceive('save')->with(\Mockery::capture($savedAccount));

        $response = $this->service()->process($this->command());

        $this->assertSame(PayInStatus::PROCESSED, $response->status);
        $this->assertSame('FP-20260101-0001', $response->providerTransactionId);
        $this->assertNull($response->errorCode);
        $this->assertSame(25000, $savedAccount->balance()->minorUnits(), 'La cuenta debe abonarse al procesarse.');
    }

    public function test_marks_failed_when_provider_rejects(): void
    {
        $this->expectLoads();
        $this->expectGatewayResolved(ChargeResult::rejected('PROVIDER_REJECTED', 'Fondos insuficientes'));
        $this->payIns->shouldReceive('save');

        $response = $this->service()->process($this->command());

        $this->assertSame(PayInStatus::FAILED, $response->status);
        $this->assertSame('PROVIDER_REJECTED', $response->errorCode);
        $this->accounts->shouldNotReceive('save');
    }

    public function test_marks_failed_when_provider_times_out(): void
    {
        $this->expectLoads();
        $this->expectGatewayResolved(ChargeResult::timeout('El proveedor no respondió en el tiempo esperado'));
        $this->payIns->shouldReceive('save');

        $response = $this->service()->process($this->command());

        $this->assertSame(PayInStatus::FAILED, $response->status);
        $this->assertSame('timeout', $response->errorCode);
    }

    public function test_marks_failed_when_provider_errors(): void
    {
        $this->expectLoads();
        $this->expectGatewayResolved(ChargeResult::error('PROVIDER_ERROR', 'Error interno del proveedor'));
        $this->payIns->shouldReceive('save');

        $response = $this->service()->process($this->command());

        $this->assertSame(PayInStatus::FAILED, $response->status);
        $this->assertSame('PROVIDER_ERROR', $response->errorCode);
    }

    public function test_persists_failed_state_and_throws_when_adapter_fails(): void
    {
        $this->expectLoads();
        $this->gateways->shouldReceive('resolve')->andReturn($this->gateway);
        $this->gateway->shouldReceive('charge')->andThrow(new \RuntimeException('Connection refused'));
        $this->payIns->shouldReceive('save');

        try {
            $this->service()->process($this->command());
            $this->fail('Debe lanzarse PayInProcessingException.');
        } catch (PayInProcessingException $exception) {
            $this->assertSame('PAYIN_PROCESSING_ERROR', $exception->errorCode());
        }
    }

    public function test_throws_when_client_does_not_exist(): void
    {
        $this->clients->shouldReceive('findById')->andReturnNull();

        $this->expectException(ClientNotFoundException::class);

        $this->service()->process($this->command());
    }

    public function test_throws_when_account_does_not_exist(): void
    {
        $this->clients->shouldReceive('findById')->andReturn($this->client);
        $this->accounts->shouldReceive('findById')->andReturnNull();

        $this->expectException(AccountNotFoundException::class);

        $this->service()->process($this->command());
    }

    public function test_throws_when_payment_method_does_not_exist(): void
    {
        $this->clients->shouldReceive('findById')->andReturn($this->client);
        $this->accounts->shouldReceive('findById')->andReturn($this->account);
        $this->paymentMethods->shouldReceive('findById')->andReturnNull();

        $this->expectException(PaymentMethodNotFoundException::class);

        $this->service()->process($this->command());
    }

    public function test_throws_when_provider_does_not_exist(): void
    {
        $this->clients->shouldReceive('findById')->andReturn($this->client);
        $this->accounts->shouldReceive('findById')->andReturn($this->account);
        $this->paymentMethods->shouldReceive('findById')->andReturn($this->method);
        $this->providers->shouldReceive('findById')->andReturnNull();

        $this->expectException(PaymentProviderNotFoundException::class);

        $this->service()->process($this->command());
    }

    public function test_throws_when_reference_is_already_used(): void
    {
        $this->payIns->shouldReceive('existsByReference')->andReturn(true);

        $this->expectException(ReferenceAlreadyUsedException::class);

        $this->service()->process($this->command(Reference::fromString('order-0001')));
    }

    public function test_nothing_is_saved_when_domain_validation_fails(): void
    {
        $inactive = PaymentMethod::reconstitute(
            $this->method->id(),
            $this->account->id(),
            $this->provider->id(),
            PaymentMethodType::CARD,
            'tok_card_abc123',
            '**** 4242',
            false,
            new \DateTimeImmutable(self::NOW),
        );
        $this->clients->shouldReceive('findById')->andReturn($this->client);
        $this->accounts->shouldReceive('findById')->andReturn($this->account);
        $this->paymentMethods->shouldReceive('findById')->andReturn($inactive);
        $this->providers->shouldReceive('findById')->andReturn($this->provider);
        $this->payIns->shouldReceive('save')->never();
        $this->payIns->shouldReceive('existsByReference')->andReturn(false);

        $this->expectException(PaymentMethodInactiveException::class);

        $this->service()->process($this->command());
    }
}
