<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Services;

use Illuminate\Contracts\Events\Dispatcher;
use PayIn\Application\Port\EventBus;

/**
 * Implementación del puerto EventBus sobre el dispatcher de eventos de
 * Laravel. Cada evento de dominio se despacha como evento de framework,
 * permitiendo listeners de infraestructura sin acoplar el dominio.
 */
final readonly class LaravelEventBus implements EventBus
{
    public function __construct(private Dispatcher $dispatcher)
    {
    }

    public function dispatch(array $events): void
    {
        foreach ($events as $event) {
            $this->dispatcher->dispatch($event);
        }
    }
}
