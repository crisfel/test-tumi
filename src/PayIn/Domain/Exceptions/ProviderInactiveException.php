<?php

declare(strict_types=1);

namespace PayIn\Domain\Exceptions;

/**
 * El proveedor de pago se encuentra desactivado en la plataforma.
 */
final class ProviderInactiveException extends PayInValidationException
{
    public function __construct(string $providerCode, ?string $payInId = null)
    {
        $message = $payInId !== null
            ? sprintf('El proveedor de pago "%s" se encuentra inactivo para el PayIn "%s".', $providerCode, $payInId)
            : sprintf('El proveedor de pago "%s" se encuentra inactivo.', $providerCode);

        parent::__construct(
            $message,
            'PROVIDER_INACTIVE',
            [
                'provider' => $providerCode,
                'payin_id' => $payInId,
            ],
        );
    }
}
