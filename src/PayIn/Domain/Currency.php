<?php

declare(strict_types=1);

namespace PayIn\Domain;

use PayIn\Domain\Exceptions\InvalidCurrencyException;

/**
 * Monedas soportadas por la plataforma (ISO 4217).
 *
 * Punto de extensión documentado: agregar una nueva moneda implica añadir
 * una case al enum y su exponente; el resto del dominio no cambia.
 */
enum Currency: string
{
    case COP = 'COP';
    case USD = 'USD';
    case EUR = 'EUR';
    case MXN = 'MXN';

    /**
     * Exponente ISO 4217 (número de decimales). Todas las soportadas usan 2.
     */
    public function exponent(): int
    {
        return 2;
    }

    public static function fromCode(string $code): self
    {
        $normalized = strtoupper(trim($code));

        return match ($normalized) {
            self::COP->value => self::COP,
            self::USD->value => self::USD,
            self::EUR->value => self::EUR,
            self::MXN->value => self::MXN,
            default => throw new InvalidCurrencyException($code),
        };
    }
}
