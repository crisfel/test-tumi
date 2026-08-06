<?php

declare(strict_types=1);

namespace PayIn\Application\Port;

use PayIn\Application\Dto\ChargeRequest;
use PayIn\Application\Result\ChargeResult;

/**
 * Contrato único que todo proveedor de pago debe implementar (Strategy).
 *
 * Agregar un proveedor nuevo = crear una clase que implemente este
 * contrato y registrarla en el Registry. Ninguna clase del dominio o de la
 * aplicación se modifica (Open/Closed Principle).
 */
interface PaymentGateway
{
    /**
     * @throws \RuntimeException si el proveedor no responde o la integración falla de forma inesperada
     */
    public function charge(ChargeRequest $request): ChargeResult;
}
