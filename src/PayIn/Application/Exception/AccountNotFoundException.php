<?php

declare(strict_types=1);

namespace PayIn\Application\Exception;

/**
 * La cuenta solicitada no existe en la plataforma.
 */
final class AccountNotFoundException extends PayInApplicationException
{
    public function __construct(string $accountId)
    {
        parent::__construct(
            sprintf('La cuenta "%s" no existe.', $accountId),
            'ACCOUNT_NOT_FOUND',
            ['account_id' => $accountId],
        );
    }
}
