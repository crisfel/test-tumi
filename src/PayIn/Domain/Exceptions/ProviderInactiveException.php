<?php

declare(strict_types=1);

namespace PayIn\Domain\Exceptions;

/**
 * El proveedor de pago se encuentra desactivado en la plataforma.
 */
final class ProviderInactiveException extends PayInValidationException
{
    public function __construct(string $payInId, string $providerCode)
    {
        parent::__construct(
            sprintf('El proveedor de pago "%s" se encuentra inactivo para el PayIn "%s".', $providerCode, $payInId),
            'PROVIDER_INACTIVE',
            [
                'payin_id' => $payInId,
                'provider' => $providerCode,
            ],
        );
    }
}
