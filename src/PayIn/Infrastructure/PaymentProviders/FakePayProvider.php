<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\PaymentProviders;

use PayIn\Application\Dto\ChargeRequest;
use PayIn\Application\Port\PaymentGateway;
use PayIn\Application\Result\ChargeResult;

/**
 * Adapter del proveedor ficticio "FakePay".
 *
 * Simula una pasarela de pago con cuatro comportamientos configurables
 * (success, rejected, timeout, error) y latencia artificial. Demuestra que
 * cualquier proveedor puede integrarse implementando el contrato
 * PaymentGateway; el orquestador no conoce esta clase.
 */
final readonly class FakePayProvider implements PaymentGateway
{
    public function __construct(private ProviderBehavior $behavior)
    {
    }

    public function charge(ChargeRequest $request): ChargeResult
    {
        if ($this->behavior->latencyMs > 0) {
            usleep($this->behavior->latencyMs * 1000);
        }

        return match ($this->behavior->behavior) {
            'rejected' => ChargeResult::rejected(
                errorCode: 'PROVIDER_REJECTED',
                message: 'La transacción fue rechazada por el emisor.',
                payload: $this->payload($request, 'declined'),
            ),
            'timeout' => ChargeResult::timeout(
                message: 'El proveedor no respondió dentro del tiempo esperado.',
                payload: $this->payload($request, 'timeout'),
            ),
            'error' => ChargeResult::error(
                errorCode: 'PROVIDER_ERROR',
                message: 'Ocurrió un error interno en el proveedor.',
                payload: $this->payload($request, 'error'),
            ),
            default => ChargeResult::success(
                providerTransactionId: 'FP-' . strtoupper(substr($request->payInId->toString(), 0, 12)),
                message: 'approved',
                payload: $this->payload($request, 'approved'),
            ),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(ChargeRequest $request, string $status): array
    {
        return [
            'provider' => 'fakepay',
            'status' => $status,
            'amount' => $request->amount->minorUnits(),
            'currency' => $request->amount->currency()->value,
            'reference' => $request->reference?->value(),
            'processed_at' => gmdate('Y-m-d\TH:i:s\Z'),
        ];
    }
}
