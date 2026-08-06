<?php

declare(strict_types=1);

namespace PayIn\Shared\Kernel\Result;

/**
 * Error tipado con código estable, mensaje para el consumidor y contexto
 * estructurado para observabilidad.
 */
final readonly class Error
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public string $code,
        public string $message,
        public array $context = [],
    ) {
    }
}
