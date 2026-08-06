<?php

declare(strict_types=1);

namespace PayIn\Domain\PayIn;

use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Exceptions\InvalidStateTransitionException;
use PayIn\Domain\Money;
use PayIn\Domain\PaymentProvider\ProviderId;

/**
 * Entidad del aggregate PayIn: el registro financiero base de la operación.
 *
 * Contiene el núcleo económico (monto, moneda, estado, referencia) y los
 * datos devueltos por el proveedor. La tabla "transactions" es reutilizable
 * por futuros tipos de operación (payout, refund) sin tocar el dominio.
 */
final class Transaction
{
    private function __construct(
        private readonly TransactionId $id,
        private readonly ClientId $clientId,
        private readonly Money $amount,
        private readonly TransactionType $type,
        private readonly \DateTimeImmutable $createdAt,
        private PayInStatus $status,
        private readonly ?Reference $reference,
        private ?ProviderId $providerId = null,
        private ?ProviderTransactionId $providerTransactionId = null,
        private ?ProviderResponse $providerResponse = null,
        private ?string $errorCode = null,
        private ?string $errorMessage = null,
        private ?\DateTimeImmutable $processedAt = null,
    ) {
    }

    public static function create(
        TransactionId $id,
        ClientId $clientId,
        Money $amount,
        ?Reference $reference,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            clientId: $clientId,
            amount: $amount,
            type: TransactionType::PAYIN,
            createdAt: $createdAt,
            status: PayInStatus::CREATED,
            reference: $reference,
        );
    }

    public static function reconstitute(
        TransactionId $id,
        ClientId $clientId,
        Money $amount,
        TransactionType $type,
        \DateTimeImmutable $createdAt,
        PayInStatus $status,
        ?Reference $reference,
        ?ProviderId $providerId,
        ?ProviderTransactionId $providerTransactionId,
        ?ProviderResponse $providerResponse,
        ?string $errorCode,
        ?string $errorMessage,
        ?\DateTimeImmutable $processedAt,
    ): self {
        return new self(
            id: $id,
            clientId: $clientId,
            amount: $amount,
            type: $type,
            createdAt: $createdAt,
            status: $status,
            reference: $reference,
            providerId: $providerId,
            providerTransactionId: $providerTransactionId,
            providerResponse: $providerResponse,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            processedAt: $processedAt,
        );
    }

    /**
     * @throws InvalidStateTransitionException si la transición no está permitida.
     */
    public function changeStatus(PayInStatus $target): void
    {
        if (!$this->status->canTransitionTo($target)) {
            throw new InvalidStateTransitionException(
                $this->status->value,
                $target->value,
                $this->id->toString(),
            );
        }

        $this->status = $target;
    }

    public function attachProviderData(
        ProviderId $providerId,
        ProviderTransactionId $providerTransactionId,
        ProviderResponse $providerResponse,
        \DateTimeImmutable $processedAt,
    ): void {
        $this->providerId = $providerId;
        $this->providerTransactionId = $providerTransactionId;
        $this->providerResponse = $providerResponse;
        $this->processedAt = $processedAt;
        $this->errorCode = null;
        $this->errorMessage = null;
    }

    public function attachFailure(string $errorCode, string $errorMessage): void
    {
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
        $this->processedAt = new \DateTimeImmutable();
    }

    public function id(): TransactionId
    {
        return $this->id;
    }

    public function clientId(): ClientId
    {
        return $this->clientId;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function type(): TransactionType
    {
        return $this->type;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function status(): PayInStatus
    {
        return $this->status;
    }

    public function reference(): ?Reference
    {
        return $this->reference;
    }

    public function providerId(): ?ProviderId
    {
        return $this->providerId;
    }

    public function providerTransactionId(): ?ProviderTransactionId
    {
        return $this->providerTransactionId;
    }

    public function providerResponse(): ?ProviderResponse
    {
        return $this->providerResponse;
    }

    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function processedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }
}
