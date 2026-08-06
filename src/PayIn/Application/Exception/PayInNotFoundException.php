<?php

declare(strict_types=1);

namespace PayIn\Application\Exception;

/**
 * El PayIn solicitado no existe en la plataforma.
 */
final class PayInNotFoundException extends PayInApplicationException
{
    public function __construct(string $payInId)
    {
        parent::__construct(
            sprintf('El PayIn "%s" no existe.', $payInId),
            'PAYIN_NOT_FOUND',
            ['payin_id' => $payInId],
        );
    }
}
