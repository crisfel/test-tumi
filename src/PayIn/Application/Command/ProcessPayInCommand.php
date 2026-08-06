<?php

declare(strict_types=1);

namespace PayIn\Application\Command;

use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Money;
use PayIn\Domain\PayIn\Reference;
use PayIn\Domain\PaymentMethod\PaymentMethodId;

/**
 * Comando inmutable que representa la intención de procesar un PayIn.
 *
 * Es construido por la capa HTTP a partir de datos ya validados; el dominio
 * nunca recibe datos en bruto del cliente.
 */
final readonly class ProcessPayInCommand
{
    public function __construct(
        public ClientId $clientId,
        public AccountId $accountId,
        public PaymentMethodId $paymentMethodId,
        public Money $amount,
        public ?Reference $reference = null,
    ) {
    }
}
