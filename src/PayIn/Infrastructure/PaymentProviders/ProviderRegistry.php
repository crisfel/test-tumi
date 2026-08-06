<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\PaymentProviders;

use PayIn\Application\Exception\PaymentGatewayNotFoundException;
use PayIn\Application\Port\PaymentGateway;
use PayIn\Application\Port\PaymentGatewayRegistry;
use PayIn\Domain\PaymentProvider\PaymentProvider;

/**
 * Registro de adapters de proveedores (Strategy + Registry).
 *
 * La resolución se hace por código de proveedor; no existe ningún
 * condicional sobre la identidad del proveedor (Open/Closed Principle).
 */
final readonly class ProviderRegistry implements PaymentGatewayRegistry
{
    /**
     * @param array<string, PaymentGateway> $gateways
     */
    public function __construct(private array $gateways)
    {
    }

    public function resolve(PaymentProvider $provider): PaymentGateway
    {
        $code = $provider->code()->value();

        return $this->gateways[$code]
            ?? throw new PaymentGatewayNotFoundException($code);
    }
}
