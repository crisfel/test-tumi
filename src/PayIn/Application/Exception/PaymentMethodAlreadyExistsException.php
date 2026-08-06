<?php

declare(strict_types=1);

namespace PayIn\Application\Exception;

/**
 * El proveedor ya posee un método de pago con el mismo token.
 *
 * El token pertenece al proveedor que lo emitió (espacio de tokenización
 * propio de cada pasarela); duplicarlo viola la unicidad (409 Conflict).
 */
final class PaymentMethodAlreadyExistsException extends PayInApplicationException
{
    public function __construct(string $providerCode, string $token)
    {
        parent::__construct(
            sprintf('El proveedor "%s" ya posee un método de pago con el token "%s".', $providerCode, $token),
            'PAYMENT_METHOD_ALREADY_EXISTS',
            [
                'provider' => $providerCode,
                'token' => $token,
            ],
        );
    }
}
