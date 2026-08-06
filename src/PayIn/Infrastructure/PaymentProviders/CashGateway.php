<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\PaymentProviders;

use PayIn\Application\Dto\ChargeRequest;
use PayIn\Application\Port\PaymentGateway;
use PayIn\Application\Result\ChargeResult;

/**
 * Adapter del proveedor "cash" (pago en efectivo).
 *
 * El efectivo no requiere pasarela: el adapter confirma la operación de
 * inmediato con una referencia generada por la plataforma. Mantiene el
 * flujo del orquestador uniforme (mismo contrato PaymentGateway).
 */
final class CashGateway implements PaymentGateway
{
    public function charge(ChargeRequest $request): ChargeResult
    {
        return ChargeResult::success(
            providerTransactionId: 'CASH-' . strtoupper(substr($request->payInId->toString(), 0, 12)),
            message: 'pagado en efectivo',
            payload: [
                'provider' => 'cash',
                'status' => 'settled',
                'amount' => $request->amount->minorUnits(),
                'currency' => $request->amount->currency()->value,
                'processed_at' => gmdate('Y-m-d\TH:i:s\Z'),
            ],
        );
    }
}
