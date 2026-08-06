<?php

declare(strict_types=1);

namespace PayIn\Application\Dto;

use PayIn\Domain\Account\Account;
use PayIn\Domain\PayIn\PayIn;
use PayIn\Domain\PaymentMethod\PaymentMethod;
use PayIn\Domain\PaymentProvider\PaymentProvider;

/**
 * Estado intermedio del flujo de procesamiento, transportado entre las
 * transacciones del orquestador.
 */
final readonly class ProcessingContext
{
    public function __construct(
        public PayIn $payIn,
        public Account $account,
        public PaymentMethod $paymentMethod,
        public PaymentProvider $provider,
    ) {
    }
}
