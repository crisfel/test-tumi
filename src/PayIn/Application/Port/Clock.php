<?php

declare(strict_types=1);

namespace PayIn\Application\Port;

/**
 * Puerto de reloj del sistema.
 *
 * Permite inyectar el tiempo en el dominio (agregados y orquestador) y
 * reproducir escenarios deterministas en las pruebas.
 */
interface Clock
{
    public function now(): \DateTimeImmutable;
}
