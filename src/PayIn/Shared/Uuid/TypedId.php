<?php

declare(strict_types=1);

namespace PayIn\Shared\Uuid;

/**
 * Base para Identificadores de Dominio tipados (ClientId, AccountId, ...).
 *
 * Cada concepto del dominio posee su propio tipo de identificador, evitando
 * confusiones entre IDs de entidades distintas (type safety en compilación).
 *
 * @phpstan-consistent-constructor
 */
abstract readonly class TypedId implements \Stringable, \JsonSerializable
{
    protected function __construct(private Uuid $uuid)
    {
    }

    public static function fromString(string $value): static
    {
        return new static(Uuid::fromString($value));
    }

    public static function generate(): static
    {
        return new static(Uuid::v7());
    }

    public function uuid(): Uuid
    {
        return $this->uuid;
    }

    public function toString(): string
    {
        return $this->uuid->toString();
    }

    public function equals(TypedId $other): bool
    {
        return static::class === $other::class && $this->uuid->equals($other->uuid);
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
