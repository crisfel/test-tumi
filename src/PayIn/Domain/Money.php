<?php

declare(strict_types=1);

namespace PayIn\Domain;

use PayIn\Domain\Exceptions\CurrencyMismatchException;
use PayIn\Domain\Exceptions\InvalidAmountException;
use PayIn\Shared\Kernel\ValueObject;

/**
 * Representa dinero en unidades menores (enteras) de una moneda.
 *
 * Decisión de diseño: los montos se almacenan como unidades menores enteras
 * (p. ej. centavos), nunca como flotantes, evitando errores de redondeo en
 * operaciones financieras. Los campos de moneda y monto son indivisibles.
 */
final readonly class Money extends ValueObject
{
    private function __construct(
        private int $minorUnits,
        private Currency $currency,
    ) {
        if ($minorUnits < 0) {
            throw new InvalidAmountException($minorUnits, $currency);
        }
    }

    public static function fromMinorUnits(int $minorUnits, Currency $currency): self
    {
        return new self($minorUnits, $currency);
    }

    public static function zero(Currency $currency): self
    {
        return new self(0, $currency);
    }

    public function minorUnits(): int
    {
        return $this->minorUnits;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function isZero(): bool
    {
        return $this->minorUnits === 0;
    }

    public function isPositive(): bool
    {
        return $this->minorUnits > 0;
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits + $other->minorUnits, $this->currency);
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self
            && $this->minorUnits === $other->minorUnits
            && $this->currency === $other->currency;
    }

    public function __toString(): string
    {
        return sprintf('%d %s', $this->minorUnits, $this->currency->value);
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new CurrencyMismatchException($this->currency, $other->currency);
        }
    }
}
