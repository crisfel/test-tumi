<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\PaymentProviders;

/**
 * Comportamiento simulado de un proveedor ficticio.
 */
final readonly class ProviderBehavior
{
    public const BEHAVIORS = ['success', 'rejected', 'timeout', 'error'];

    public function __construct(
        public string $behavior,
        public int $latencyMs = 0,
    ) {
        if (!in_array($behavior, self::BEHAVIORS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Comportamiento "%s" inválido. Valores permitidos: %s.',
                $behavior,
                implode(', ', self::BEHAVIORS),
            ));
        }

        if ($latencyMs < 0) {
            throw new \InvalidArgumentException('La latencia no puede ser negativa.');
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromConfig(array $config): self
    {
        return new self(
            behavior: is_string($config['behavior'] ?? null) ? $config['behavior'] : 'success',
            latencyMs: is_int($config['latency_ms'] ?? null) ? $config['latency_ms'] : 0,
        );
    }
}
