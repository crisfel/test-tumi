<?php

declare(strict_types=1);

namespace PayIn\Application\Exception;

/**
 * La cuenta ya posee un método de pago con el mismo token.
 *
 * Un token no puede registrarse dos veces en la misma cuenta (UNIQUE en
 * BD); duplicarlo viola la unicidad (respuesta 409 Conflict).
 */
final class PaymentMethodAlreadyExistsException extends PayInApplicationException
{
    public function __construct(string $accountId, string $token)
    {
        parent::__construct(
            sprintf('La cuenta "%s" ya posee un método de pago con el token "%s".', $accountId, $token),
            'PAYMENT_METHOD_ALREADY_EXISTS',
            [
                'account_id' => $accountId,
                'token' => $token,
            ],
        );
    }
}
