<?php

declare(strict_types=1);

namespace PayIn\Application\Port;

use PayIn\Application\Exception\PaymentGatewayNotFoundException;
use PayIn\Domain\PaymentProvider\PaymentProvider;

/**
 * Registro de adapters de proveedores (Registry + Strategy).
 *
 * Resuelve el adapter asociado al código de un proveedor. Nunca existe
 * lógica del tipo "if (provider === ...)": la resolución es por contrato.
 */
interface PaymentGatewayRegistry
{
    /**
     * @throws PaymentGatewayNotFoundException si no existe adapter para el proveedor
     */
    public function resolve(PaymentProvider $provider): PaymentGateway;
}
