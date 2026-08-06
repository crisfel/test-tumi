<?php

declare(strict_types=1);

namespace PayIn\Domain\PaymentProvider;

/**
 * Aggregate Root: Proveedor de pago registrado en la plataforma.
 *
 * El catálogo de proveedores se persiste y se siembra con los adapters
 * disponibles. Su "code" es la clave de resolución en el Registry.
 */
final readonly class PaymentProvider
{
    private const MAX_NAME_LENGTH = 100;

    /**
     * @param array<string, mixed> $configuration
     */
    private function __construct(
        private ProviderId $id,
        private ProviderCode $code,
        private string $name,
        private bool $active,
        private array $configuration,
    ) {
    }

    /**
     * @param array<string, mixed> $configuration
     */
    public static function register(
        ProviderId $id,
        ProviderCode $code,
        string $name,
        bool $active,
        array $configuration = [],
    ): self {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > self::MAX_NAME_LENGTH) {
            throw new \InvalidArgumentException('El nombre del proveedor no puede exceder 100 caracteres.');
        }

        return new self($id, $code, $name, $active, $configuration);
    }

    /**
     * @param array<string, mixed> $configuration
     */
    public static function reconstitute(
        ProviderId $id,
        ProviderCode $code,
        string $name,
        bool $active,
        array $configuration = [],
    ): self {
        return new self($id, $code, $name, $active, $configuration);
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function id(): ProviderId
    {
        return $this->id;
    }

    public function code(): ProviderCode
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, mixed>
     */
    public function configuration(): array
    {
        return $this->configuration;
    }
}
