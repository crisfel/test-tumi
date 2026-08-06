<?php

declare(strict_types=1);

namespace PayIn\Application\Exception;

/**
 * Fallo inesperado de la integración con el proveedor durante el cobro.
 *
 * El PayIn queda persistido en estado FAILED antes de propagarse, de modo
 * que la operación nunca queda en un estado inconsistente.
 */
final class PayInProcessingException extends PayInApplicationException
{
    public function __construct(
        string $payInId,
        string $providerCode,
        string $reason,
    ) {
        parent::__construct(
            sprintf('Ocurrió un error inesperado procesando el PayIn "%s" con el proveedor "%s".', $payInId, $providerCode),
            'PAYIN_PROCESSING_ERROR',
            [
                'payin_id' => $payInId,
                'provider' => $providerCode,
                'reason' => $reason,
            ],
        );
    }
}
