<?php

declare(strict_types=1);

namespace PayIn\Application\Exception;

/**
 * Conflicto de concurrencia al persistir un PayIn (locking optimista).
 *
 * Ocurre cuando dos procesos intentan actualizar el mismo registro con una
 * versión desactualizada; la operación debe reintentarse.
 */
final class PayInConcurrencyException extends PayInApplicationException
{
    public function __construct(string $payInId)
    {
        parent::__construct(
            sprintf('El PayIn "%s" fue modificado por otro proceso; reintente la operación.', $payInId),
            'PAYIN_CONCURRENCY_CONFLICT',
            ['payin_id' => $payInId],
        );
    }
}
