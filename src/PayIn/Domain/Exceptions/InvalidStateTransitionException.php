<?php

declare(strict_types=1);

namespace PayIn\Domain\Exceptions;

/**
 * Se lanza al intentar una transición de estado no permitida.
 */
final class InvalidStateTransitionException extends PayInDomainException
{
    public function __construct(
        string $currentStatus,
        string $targetStatus,
        string $payInId,
    ) {
        parent::__construct(
            sprintf(
                'No es posible transitar el PayIn "%s" del estado "%s" al estado "%s".',
                $payInId,
                $currentStatus,
                $targetStatus,
            ),
            'PAYIN_STATE_INVALID',
            [
                'payin_id' => $payInId,
                'current' => $currentStatus,
                'target' => $targetStatus,
            ],
        );
    }
}
