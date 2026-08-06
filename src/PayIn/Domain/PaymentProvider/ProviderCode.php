<?php

declare(strict_types=1);

namespace PayIn\Domain\PaymentProvider;

use PayIn\Domain\Exceptions\InvalidProviderCodeException;
use PayIn\Shared\Kernel\ValueObject;

/**
 * Código técnico que identifica un proveedor dentro del registro
 * (p. ej. "fakepay", "sandboxpay"). Es la clave de resolución del Registry.
 */
final readonly class ProviderCode extends ValueObject
{
    private const PATTERN = '/^[a-z][a-z0-9_]{1,31}$/';

    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        if (preg_match(self::PATTERN, $value) !== 1) {
            throw new InvalidProviderCodeException($value);
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
