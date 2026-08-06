<?php

declare(strict_types=1);

namespace PayIn\Application\Result;

/**
 * Resultado normalizado del cobro en el proveedor (Result Pattern).
 *
 * Inmutable; los adapters devuelven siempre un ChargeResult, incluso ante
 * timeouts o rechazos, para que el orquestador aplique la transición de
 * estado correspondiente sin try/catch de control de flujo.
 */
final readonly class ChargeResult
{
    /**
     * @param array<string, mixed> $payload
     */
    private function __construct(
        public ChargeOutcome $outcome,
        public string $message,
        public ?string $providerTransactionId,
        public ?string $errorCode,
        public array $payload,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function success(string $providerTransactionId, string $message = '', array $payload = []): self
    {
        return new self(ChargeOutcome::SUCCESS, $message, $providerTransactionId, null, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function rejected(string $errorCode, string $message, array $payload = []): self
    {
        return new self(ChargeOutcome::REJECTED, $message, null, $errorCode, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function timeout(string $message, array $payload = []): self
    {
        return new self(ChargeOutcome::TIMEOUT, $message, null, null, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function error(string $errorCode, string $message, array $payload = []): self
    {
        return new self(ChargeOutcome::ERROR, $message, null, $errorCode, $payload);
    }

    public function isSuccess(): bool
    {
        return $this->outcome === ChargeOutcome::SUCCESS;
    }
}
