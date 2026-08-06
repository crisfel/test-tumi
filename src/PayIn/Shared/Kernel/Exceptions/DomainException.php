<?php

declare(strict_types=1);

namespace PayIn\Shared\Kernel\Exceptions;

/**
 * Base de todas las excepciones de dominio.
 *
 * Cada excepción del dominio debe extender esta clase y proporcionar un
 * código de error estable (utilizado por la API para respuestas homogéneas)
 * y un contexto estructurado (utilizado por el logging).
 */
abstract class DomainException extends \RuntimeException
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message,
        private readonly string $errorCode,
        private readonly array $context = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }
}
