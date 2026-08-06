<?php

declare(strict_types=1);

namespace PayIn\Domain\Exceptions;

/**
 * La cuenta no pertenece al cliente que origina el PayIn.
 */
final class AccountNotBelongingToClientException extends PayInValidationException
{
    public function __construct(string $payInId)
    {
        parent::__construct(
            sprintf('La cuenta destino no pertenece al cliente que origina el PayIn "%s".', $payInId),
            'ACCOUNT_NOT_OWNED',
            ['payin_id' => $payInId],
        );
    }
}
