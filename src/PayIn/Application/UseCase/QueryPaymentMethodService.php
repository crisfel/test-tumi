<?php

declare(strict_types=1);

namespace PayIn\Application\UseCase;

use PayIn\Application\Exception\PaymentMethodNotFoundException;
use PayIn\Domain\Contracts\PaymentMethodRepository;
use PayIn\Domain\PaymentMethod\PaymentMethod;
use PayIn\Domain\PaymentMethod\PaymentMethodId;

/**
 * Caso de uso de consulta de un método de pago por su identificador.
 */
final readonly class QueryPaymentMethodService
{
    public function __construct(private PaymentMethodRepository $paymentMethods)
    {
    }

    public function findById(PaymentMethodId $id): ?PaymentMethod
    {
        return $this->paymentMethods->findById($id);
    }

    /**
     * @throws PaymentMethodNotFoundException
     */
    public function findByIdOrFail(PaymentMethodId $id): PaymentMethod
    {
        return $this->paymentMethods->findById($id)
            ?? throw new PaymentMethodNotFoundException($id->toString());
    }
}
