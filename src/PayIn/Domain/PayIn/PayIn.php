<?php

declare(strict_types=1);

namespace PayIn\Domain\PayIn;

use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Exceptions\InvalidStateTransitionException;
use PayIn\Domain\Money;
use PayIn\Domain\PaymentMethod\PaymentMethodId;
use PayIn\Domain\PaymentProvider\ProviderId;
use PayIn\Shared\Kernel\DomainEvent;

/**
 * Aggregate Root: operación PayIn (ingreso de fondos).
 *
 * Compone una Transaction (núcleo financiero) más los datos específicos de
 * un PayIn (cuenta receptora, método de pago y comisiones). Todas las
 * invariantes de estado se protegen dentro del aggregate; los eventos de
 * dominio se registran y se liberan con releaseEvents().
 */
final class PayIn
{
    /**
     * @param list<DomainEvent> $domainEvents
     */
    private function __construct(
        private readonly Transaction $transaction,
        private readonly AccountId $accountId,
        private readonly PaymentMethodId $paymentMethodId,
        private readonly Money $fees,
        private array $domainEvents = [],
    ) {
    }

    /**
     * Factory del aggregate: construye un PayIn en estado CREATED y registra
     * el evento PayInCreated. No persiste nada por sí mismo.
     */
    public static function create(
        TransactionId $id,
        ClientId $clientId,
        AccountId $accountId,
        PaymentMethodId $paymentMethodId,
        Money $amount,
        Money $fees,
        ?Reference $reference,
        \DateTimeImmutable $createdAt,
    ): self {
        $payIn = new self(
            transaction: Transaction::create($id, $clientId, $amount, $reference, $createdAt),
            accountId: $accountId,
            paymentMethodId: $paymentMethodId,
            fees: $fees,
        );
        $payIn->recordEvent(new Events\PayInCreated($id));

        return $payIn;
    }

    public static function reconstitute(
        TransactionId $id,
        ClientId $clientId,
        AccountId $accountId,
        PaymentMethodId $paymentMethodId,
        Money $amount,
        Money $fees,
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
            transaction: Transaction::reconstitute(
                $id,
                $clientId,
                $amount,
                $type,
                $createdAt,
                $status,
                $reference,
                $providerId,
                $providerTransactionId,
                $providerResponse,
                $errorCode,
                $errorMessage,
                $processedAt,
            ),
            accountId: $accountId,
            paymentMethodId: $paymentMethodId,
            fees: $fees,
        );
    }

    /**
     * El PayIn ha superado todas las validaciones de dominio.
     *
     * @throws InvalidStateTransitionException si no se encuentra en CREATED.
     */
    public function markValidated(): void
    {
        $this->transitionTo(PayInStatus::VALIDATED);
        $this->recordEvent(new Events\PayInValidated($this->id()));
    }

    /**
     * El PayIn entra en vuelo: se ha delegado el cobro al proveedor.
     *
     * @throws InvalidStateTransitionException si no se encuentra en VALIDATED.
     */
    public function markProcessing(): void
    {
        $this->transitionTo(PayInStatus::PROCESSING);
        $this->recordEvent(new Events\PayInProcessing($this->id()));
    }

    /**
     * El proveedor confirmó el cobro: el PayIn queda PROCESSED y se adjunta
     * la evidencia de la operación.
     *
     * @throws InvalidStateTransitionException si no se encuentra en PROCESSING.
     */
    public function markProcessed(
        ProviderId $providerId,
        ProviderTransactionId $providerTransactionId,
        ProviderResponse $providerResponse,
        \DateTimeImmutable $processedAt,
    ): void {
        $this->transitionTo(PayInStatus::PROCESSED);
        $this->transaction->attachProviderData($providerId, $providerTransactionId, $providerResponse, $processedAt);
        $this->recordEvent(new Events\PayInProcessed($this->id(), $providerTransactionId));
    }

    /**
     * La operación fracasó (rechazo, timeout o error): el PayIn queda FAILED
     * con el código y mensaje del fallo.
     *
     * @throws InvalidStateTransitionException si ya se encuentra en un estado terminal.
     */
    public function markFailed(string $errorCode, string $errorMessage): void
    {
        $this->transitionTo(PayInStatus::FAILED);
        $this->transaction->attachFailure($errorCode, $errorMessage);
        $this->recordEvent(new Events\PayInFailed($this->id(), $errorCode, $errorMessage));
    }

    /**
     * @return list<DomainEvent>
     */
    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    private function transitionTo(PayInStatus $target): void
    {
        $this->transaction->changeStatus($target);
    }

    private function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function id(): TransactionId
    {
        return $this->transaction->id();
    }

    public function clientId(): ClientId
    {
        return $this->transaction->clientId();
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function paymentMethodId(): PaymentMethodId
    {
        return $this->paymentMethodId;
    }

    public function amount(): Money
    {
        return $this->transaction->amount();
    }

    public function type(): TransactionType
    {
        return $this->transaction->type();
    }

    public function fees(): Money
    {
        return $this->fees;
    }

    public function reference(): ?Reference
    {
        return $this->transaction->reference();
    }

    public function status(): PayInStatus
    {
        return $this->transaction->status();
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->transaction->createdAt();
    }

    public function processedAt(): ?\DateTimeImmutable
    {
        return $this->transaction->processedAt();
    }

    public function providerId(): ?ProviderId
    {
        return $this->transaction->providerId();
    }

    public function providerTransactionId(): ?ProviderTransactionId
    {
        return $this->transaction->providerTransactionId();
    }

    public function providerResponse(): ?ProviderResponse
    {
        return $this->transaction->providerResponse();
    }

    public function errorCode(): ?string
    {
        return $this->transaction->errorCode();
    }

    public function errorMessage(): ?string
    {
        return $this->transaction->errorMessage();
    }
}
