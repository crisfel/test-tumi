<?php

declare(strict_types=1);

namespace PayIn\Application\Exception;

/**
 * El cliente ya posee una cuenta en la moneda solicitada.
 *
 * La plataforma permite una única cuenta por cliente y moneda (UNIQUE en
 * BD); duplicarla viola la unicidad (respuesta 409 Conflict).
 */
final class AccountAlreadyExistsException extends PayInApplicationException
{
    public function __construct(string $clientId, string $currency)
    {
        parent::__construct(
            sprintf('El cliente "%s" ya posee una cuenta en la moneda "%s".', $clientId, $currency),
            'ACCOUNT_ALREADY_EXISTS',
            [
                'client_id' => $clientId,
                'currency' => $currency,
            ],
        );
    }
}
