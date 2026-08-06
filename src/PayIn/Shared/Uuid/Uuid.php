<?php

declare(strict_types=1);

namespace PayIn\Shared\Uuid;

use PayIn\Shared\Kernel\Exceptions\InvalidUuidException;
use Symfony\Component\Uid\Uuid as SymfonyUuid;

/**
 * Value Object inmmutable que encapsula un UUID v7 (ordenable por tiempo).
 *
 * Utiliza symfony/uid, dependencia ya provista por el framework, sin añadir
 * dependencias nuevas. El UUIDv7 se elige por su orden cronológico, lo que
 * optimiza el rendimiento de los índices B-tree de MySQL.
 */
final readonly class Uuid implements \Stringable, \JsonSerializable
{
    private function __construct(private SymfonyUuid $uuid)
    {
    }

    public static function fromString(string $value): self
    {
        if (!self::isValid($value)) {
            throw new InvalidUuidException($value);
        }

        return new self(SymfonyUuid::fromString($value));
    }

    public static function v7(): self
    {
        return new self(SymfonyUuid::v7());
    }

    public static function isValid(string $value): bool
    {
        return SymfonyUuid::isValid($value);
    }

    public function toString(): string
    {
        return $this->uuid->toString();
    }

    public function equals(self $other): bool
    {
        return $this->uuid->equals($other->uuid);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function jsonSerialize(): string
    {
        return $this->toString();
    }
}
