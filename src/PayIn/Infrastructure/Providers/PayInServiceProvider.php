<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use PayIn\Application\Port\Clock;
use PayIn\Application\Port\EventBus;
use PayIn\Application\Port\Logger;
use PayIn\Application\Port\PaymentGatewayRegistry;
use PayIn\Application\Port\TransactionManager;
use PayIn\Domain\Contracts\AccountRepository;
use PayIn\Domain\Contracts\ClientRepository;
use PayIn\Domain\Contracts\PayInRepository;
use PayIn\Domain\Contracts\PaymentMethodRepository;
use PayIn\Domain\Contracts\PaymentProviderRepository;
use PayIn\Domain\PayIn\Events\PayInCreated;
use PayIn\Domain\PayIn\Events\PayInFailed;
use PayIn\Domain\PayIn\Events\PayInProcessed;
use PayIn\Domain\PayIn\Events\PayInProcessing;
use PayIn\Domain\PayIn\Events\PayInValidated;
use PayIn\Infrastructure\Observers\PayInEventLogger;
use PayIn\Infrastructure\PaymentProviders\FakePayProvider;
use PayIn\Infrastructure\PaymentProviders\ProviderBehavior;
use PayIn\Infrastructure\PaymentProviders\ProviderRegistry;
use PayIn\Infrastructure\PaymentProviders\SandboxPayProvider;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\AccountMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\ClientMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\PayInMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\PaymentMethodMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\PaymentProviderMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Repositories\EloquentAccountRepository;
use PayIn\Infrastructure\Persistence\Eloquent\Repositories\EloquentClientRepository;
use PayIn\Infrastructure\Persistence\Eloquent\Repositories\EloquentPayInRepository;
use PayIn\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentMethodRepository;
use PayIn\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentProviderRepository;
use PayIn\Infrastructure\Services\LaravelEventBus;
use PayIn\Infrastructure\Services\LaravelLogger;
use PayIn\Infrastructure\Services\LaravelTransactionManager;
use PayIn\Infrastructure\Services\SystemClock;

/**
 * Wiring del componente PayIn (Infrastructure).
 *
 * Único lugar donde se enlazan contratos (Ports) con implementaciones
 * (Adapters). El dominio y la aplicación no conocen esta clase.
 */
final class PayInServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerPersistence();
        $this->registerPorts();
        $this->registerGateways();
    }

    public function boot(): void
    {
        foreach ([
            PayInCreated::class,
            PayInValidated::class,
            PayInProcessing::class,
            PayInProcessed::class,
            PayInFailed::class,
        ] as $event) {
            Event::listen($event, [PayInEventLogger::class, 'handle']);
        }
    }

    private function registerPersistence(): void
    {
        $this->app->bind(ClientRepository::class, EloquentClientRepository::class);
        $this->app->bind(AccountRepository::class, EloquentAccountRepository::class);
        $this->app->bind(PaymentMethodRepository::class, EloquentPaymentMethodRepository::class);
        $this->app->bind(PaymentProviderRepository::class, EloquentPaymentProviderRepository::class);
        $this->app->bind(PayInRepository::class, EloquentPayInRepository::class);

        $this->app->bind(ClientMapper::class);
        $this->app->bind(AccountMapper::class);
        $this->app->bind(PaymentMethodMapper::class);
        $this->app->bind(PaymentProviderMapper::class);
        $this->app->bind(PayInMapper::class);
    }

    private function registerPorts(): void
    {
        $this->app->bind(Clock::class, SystemClock::class);
        $this->app->bind(TransactionManager::class, LaravelTransactionManager::class);
        $this->app->bind(EventBus::class, LaravelEventBus::class);
        $this->app->bind(Logger::class, LaravelLogger::class);
    }

    private function registerGateways(): void
    {
        $this->app->singleton(PaymentGatewayRegistry::class, function (Application $app): ProviderRegistry {
            /** @var array<string, string> $gatewayClasses */
            $gatewayClasses = (array) config('payin.gateways', []);
            $providerConfigs = (array) config('payin.providers', []);

            $gateways = [];

            foreach ($gatewayClasses as $code => $class) {
                /** @var array<string, mixed> $providerConfig */
                $providerConfig = (array) ($providerConfigs[$code] ?? []);
                $behavior = ProviderBehavior::fromConfig($providerConfig);

                $gateways[$code] = match ($class) {
                    FakePayProvider::class => new FakePayProvider($behavior),
                    SandboxPayProvider::class => new SandboxPayProvider($behavior),
                    default => $app->make($class),
                };
            }

            return new ProviderRegistry($gateways);
        });
    }
}
