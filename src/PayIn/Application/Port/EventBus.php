<?php

declare(strict_types=1);

namespace PayIn\Application\Port;

use PayIn\Shared\Kernel\DomainEvent;

/**
 * Puerto de despacho de eventos de dominio.
 *
 * La implementación de infraestructura puede despachar de forma síncrona
 * (logging), a colas o a un bus de eventos sin tocar la aplicación.
 */
interface EventBus
{
    /**
     * @param list<DomainEvent> $events
     */
    public function dispatch(array $events): void;
}
