<?php

declare(strict_types=1);

namespace PayIn\Application\Exception;

/**
 * No existe un adapter registrado para el proveedor solicitado.
 */
final class PaymentGatewayNotFoundException extends PayInApplicationException
{
    public function __construct(string $providerCode)
    {
        parent::__construct(
            sprintf('No existe un adaptador de pago registrado para el proveedor "%s".', $providerCode),
            'PROVIDER_GATEWAY_NOT_FOUND',
            ['provider_code' => $providerCode],
        );
    }
}
