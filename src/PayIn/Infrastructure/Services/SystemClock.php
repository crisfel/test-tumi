<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Services;

use PayIn\Application\Port\Clock;

/**
 * Implementación del puerto Clock con la hora real del sistema.
 */
final class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now');
    }
}
