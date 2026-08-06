<?php

declare(strict_types=1);

namespace PayIn\Domain;

use PayIn\Domain\Exceptions\InvalidEmailException;
use PayIn\Shared\Kernel\ValueObject;

/**
 * Dirección de correo electrónico validada y normalizada a minúsculas.
 *
 * La validación es propia del dominio y NO utiliza la regla "email" de
 * Laravel, cuya versión 11.x presenta una vulnerabilidad de inyección CRLF
 * sin corrección publicada (CVE-2026-48019, ver README sección Seguridad).
 */
final readonly class Email extends ValueObject
{
    private const PATTERN = '/^[A-Za-z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?(?:\.[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?)+$/';

    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));

        if ($normalized === '' || preg_match(self::PATTERN, $normalized) !== 1) {
            throw new InvalidEmailException($value);
        }

        return new self($normalized);
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
