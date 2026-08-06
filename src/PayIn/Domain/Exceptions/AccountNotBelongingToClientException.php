<?php

declare(strict_types=1);

namespace PayIn\Domain\Exceptions;

/**
 * La cuenta de origen no pertenece al cliente que origina el PayIn.
 */
final class AccountNotBelongingToClientException extends PayInValidationException
{
    public function __construct(string $accountId, string $payInId)
    {
        parent::__construct(
            sprintf('La cuenta de origen "%s" no pertenece al cliente que origina el PayIn "%s".', $accountId, $payInId),
            'ACCOUNT_NOT_OWNED',
            [
                'account_id' => $accountId,
                'payin_id' => $payInId,
            ],
        );
    }
}
