<?php

declare(strict_types=1);

namespace PayIn\Application\Dto;

use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Money;
use PayIn\Domain\PayIn\Reference;
use PayIn\Domain\PayIn\TransactionId;
use PayIn\Domain\PaymentMethod\PaymentMethodId;
use PayIn\Domain\PaymentMethod\PaymentMethodType;
use PayIn\Domain\PaymentProvider\ProviderCode;

/**
 * Petición normalizada enviada al adaptador del proveedor de pago.
 *
 * Los adapters traducen este contrato a la API específica del proveedor;
 * el dominio y la aplicación nunca conocen los detalles de cada proveedor.
 */
final readonly class ChargeRequest
{
    public function __construct(
        public TransactionId $payInId,
        public ClientId $clientId,
        public AccountId $accountId,
        public PaymentMethodId $paymentMethodId,
        public Money $amount,
        public ?Reference $reference,
        public PaymentMethodType $methodType,
        public string $methodToken,
        public ProviderCode $providerCode,
    ) {
    }
}
