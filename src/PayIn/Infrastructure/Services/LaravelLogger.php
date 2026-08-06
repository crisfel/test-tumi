<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Services;

use Illuminate\Contracts\Foundation\Application;
use PayIn\Application\Port\Logger;

/**
 * Implementación del puerto Logger sobre Monolog (canal "payin").
 *
 * El canal se configura en config/logging.php con formato estructurado
 * (JSON en producción).
 */
final readonly class LaravelLogger implements Logger
{
    private const CHANNEL = 'payin';

    public function __construct(private Application $app)
    {
    }

    public function info(string $message, array $context = []): void
    {
        $this->app->make('log')->channel(self::CHANNEL)->info($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->app->make('log')->channel(self::CHANNEL)->error($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->app->make('log')->channel(self::CHANNEL)->warning($message, $context);
    }
}
