<?php

declare(strict_types=1);

namespace PayIn\Shared\Kernel\Result;

/**
 * Resultado inmutable de una operación (Result Pattern).
 *
 * Los errores se modelan como valores (Error) en lugar de excepciones de
 * control de flujo. Ideal para operaciones donde el fallo es un resultado
 * esperado (p. ej. respuestas de proveedores de pago).
 */
final readonly class Result
{
    private function __construct(
        private bool $successful,
        private mixed $value,
        private ?Error $error,
    ) {
    }

    public static function success(mixed $value = null): self
    {
        return new self(true, $value, null);
    }

    public static function failure(Error $error): self
    {
        return new self(false, null, $error);
    }

    public function isSuccess(): bool
    {
        return $this->successful;
    }

    public function isFailure(): bool
    {
        return !$this->successful;
    }

    /**
     * @throws \LogicException cuando el resultado no fue exitoso.
     */
    public function value(): mixed
    {
        if (!$this->successful) {
            throw new \LogicException('No se puede acceder al valor de un Result fallido.');
        }

        return $this->value;
    }

    /**
     * @throws \LogicException cuando el resultado fue exitoso.
     */
    public function error(): Error
    {
        if (!$this->error instanceof \PayIn\Shared\Kernel\Result\Error) {
            throw new \LogicException('No se puede acceder al error de un Result exitoso.');
        }

        return $this->error;
    }
}
