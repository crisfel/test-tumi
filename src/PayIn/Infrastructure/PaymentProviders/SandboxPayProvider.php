<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\PaymentProviders;

use PayIn\Application\Dto\ChargeRequest;
use PayIn\Application\Port\PaymentGateway;
use PayIn\Application\Result\ChargeResult;

/**
 * Adapter del proveedor ficticio "SandboxPay".
 *
 * Segundo proveedor que implementa el MISMO contrato PaymentGateway con
 * formatos y códigos propios, demostrando que la heterogeneidad de los
 * proveedores se absorbe en el adapter y nunca llega al orquestador.
 */
final readonly class SandboxPayProvider implements PaymentGateway
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
                errorCode: 'SP_REJECTED_FUNDS',
                message: 'Fondos insuficientes en la cuenta del cliente.',
                payload: $this->payload($request, 'rejected'),
            ),
            'timeout' => ChargeResult::timeout(
                message: 'SandboxPay no confirmó la operación a tiempo.',
                payload: $this->payload($request, 'timeout'),
            ),
            'error' => ChargeResult::error(
                errorCode: 'SP_INTERNAL_ERROR',
                message: 'SandboxPay reportó un error interno.',
                payload: $this->payload($request, 'error'),
            ),
            default => ChargeResult::success(
                providerTransactionId: 'SP-' . strtoupper(substr(hash('sha256', $request->payInId->toString()), 0, 12)),
                message: 'OK',
                payload: $this->payload($request, 'success'),
            ),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(ChargeRequest $request, string $status): array
    {
        return [
            'gateway' => 'sandboxpay',
            'result' => $status,
            'total' => $request->amount->minorUnits(),
            'iso' => $request->amount->currency()->value,
            'client_reference' => $request->reference?->value(),
        ];
    }
}
