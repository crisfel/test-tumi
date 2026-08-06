<?php

declare(strict_types=1);

namespace PayIn\Domain\PayIn;

use PayIn\Shared\Kernel\ValueObject;

/**
 * Respuesta cruda (opaca) del proveedor de pago, preservada para auditoría.
 *
 * El dominio no interpreta su contenido: sólo lo almacena. Su estructura
 * depende de cada proveedor (los adapters la normalizan al cargar).
 */
final readonly class ProviderResponse extends ValueObject
{
    /**
     * @param array<string, mixed> $payload
     */
    private function __construct(private array $payload)
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self($payload);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->payload;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self
            && json_encode($this->payload, JSON_THROW_ON_ERROR) === json_encode($other->payload, JSON_THROW_ON_ERROR);
    }

    public function __toString(): string
    {
        return json_encode($this->payload, JSON_THROW_ON_ERROR);
    }
}
