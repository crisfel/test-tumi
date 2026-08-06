<?php

declare(strict_types=1);

namespace PayIn\Domain\PayIn;

use PayIn\Domain\Exceptions\InvalidProviderTransactionIdException;
use PayIn\Shared\Kernel\ValueObject;

/**
 * Identificador de la transacción asignado por el proveedor de pago.
 */
final readonly class ProviderTransactionId extends ValueObject
{
    private const MAX_LENGTH = 64;

    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        if (trim($value) === '' || mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidProviderTransactionIdException($value);
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
