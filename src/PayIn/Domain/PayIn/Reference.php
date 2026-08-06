<?php

declare(strict_types=1);

namespace PayIn\Domain\PayIn;

use PayIn\Domain\Exceptions\InvalidReferenceException;
use PayIn\Shared\Kernel\ValueObject;

/**
 * Referencia externa (idempotente) proporcionada por el cliente.
 *
 * La unicidad de la referencia en persistencia garantiza que una petición
 * repetida no duplique la operación.
 */
final readonly class Reference extends ValueObject
{
    private const PATTERN = '/^[A-Za-z0-9_-]{4,64}$/';

    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        if (preg_match(self::PATTERN, $value) !== 1) {
            throw new InvalidReferenceException($value);
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
