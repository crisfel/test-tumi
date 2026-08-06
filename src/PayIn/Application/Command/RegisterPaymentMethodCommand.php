<?php

declare(strict_types=1);

namespace PayIn\Application\Command;

use PayIn\Domain\PaymentMethod\PaymentMethodType;
use PayIn\Domain\PaymentProvider\ProviderCode;

/**
 * Comando inmutable que representa la intención de registrar un método de
 * pago (instrumento independiente) en un proveedor.
 */
final readonly class RegisterPaymentMethodCommand
{
    public function __construct(
        public ProviderCode $providerCode,
        public PaymentMethodType $type,
        public string $token,
        public string $detailsMasked,
    ) {
    }
}
