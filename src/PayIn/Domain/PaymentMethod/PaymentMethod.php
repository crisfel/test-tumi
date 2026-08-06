<?php

declare(strict_types=1);

namespace PayIn\Domain\PaymentMethod;

use PayIn\Domain\PaymentProvider\PaymentProvider;
use PayIn\Domain\PaymentProvider\ProviderId;

/**
 * Aggregate Root: Método de pago (instrumento de cobro).
 *
 * Es un instrumento independiente: no pertenece a ningún cliente ni a
 * ninguna cuenta. Lleva el token opaco emitido por el proveedor que lo
 * tokenizó (estándar de industria: el token pertenece a la pasarela), por
 * lo que está vinculado al proveedor que lo procesará.
 */
final readonly class PaymentMethod
{
    private const MAX_TOKEN_LENGTH = 255;

    private function __construct(
        private PaymentMethodId $id,
        private ProviderId $providerId,
        private PaymentMethodType $type,
        private string $token,
        private string $detailsMasked,
        private bool $active,
        private \DateTimeImmutable $createdAt,
    ) {
    }

    public static function register(
        PaymentMethodId $id,
        ProviderId $providerId,
        PaymentMethodType $type,
        string $token,
        string $detailsMasked,
        \DateTimeImmutable $createdAt,
    ): self {
        if (trim($token) === '' || mb_strlen($token) > self::MAX_TOKEN_LENGTH) {
            throw new \InvalidArgumentException('El token del método de pago no puede exceder 255 caracteres.');
        }

        return new self($id, $providerId, $type, $token, $detailsMasked, true, $createdAt);
    }

    public static function reconstitute(
        PaymentMethodId $id,
        ProviderId $providerId,
        PaymentMethodType $type,
        string $token,
        string $detailsMasked,
        bool $active,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $providerId, $type, $token, $detailsMasked, $active, $createdAt);
    }

    public function usesProvider(PaymentProvider $provider): bool
    {
        return $this->providerId->equals($provider->id());
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function id(): PaymentMethodId
    {
        return $this->id;
    }

    public function providerId(): ProviderId
    {
        return $this->providerId;
    }

    public function type(): PaymentMethodType
    {
        return $this->type;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function detailsMasked(): string
    {
        return $this->detailsMasked;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
