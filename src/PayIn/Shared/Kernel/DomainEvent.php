<?php

declare(strict_types=1);

namespace PayIn\Shared\Kernel;

/**
 * Marcador de eventos de dominio emitidos por los aggregates.
 *
 * Los eventos se despachan mediante el puerto EventBus del Application layer,
 * lo que permite observar el dominio sin acoplarlo a infraestructura.
 */
interface DomainEvent
{
}
