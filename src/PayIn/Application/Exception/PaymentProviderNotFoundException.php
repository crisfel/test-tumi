<?php

declare(strict_types=1);

namespace PayIn\Application\Exception;

/**
 * El proveedor de pago solicitado no existe en la plataforma.
 */
final class PaymentProviderNotFoundException extends PayInApplicationException
{
    public function __construct(string $providerId)
    {
        parent::__construct(
            sprintf('El proveedor de pago "%s" no existe.', $providerId),
            'PROVIDER_NOT_FOUND',
            ['provider_id' => $providerId],
        );
    }
}
