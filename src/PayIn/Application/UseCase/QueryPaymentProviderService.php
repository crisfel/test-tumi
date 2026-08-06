<?php

declare(strict_types=1);

namespace PayIn\Application\UseCase;

use PayIn\Application\Exception\PaymentProviderNotFoundException;
use PayIn\Domain\Contracts\PaymentProviderRepository;
use PayIn\Domain\PaymentProvider\PaymentProvider;
use PayIn\Domain\PaymentProvider\ProviderId;

/**
 * Caso de uso de consulta de un proveedor de pago por su identificador.
 */
final readonly class QueryPaymentProviderService
{
    public function __construct(private PaymentProviderRepository $providers)
    {
    }

    public function findById(ProviderId $id): ?PaymentProvider
    {
        return $this->providers->findById($id);
    }

    /**
     * @throws PaymentProviderNotFoundException
     */
    public function findByIdOrFail(ProviderId $id): PaymentProvider
    {
        return $this->providers->findById($id)
            ?? throw new PaymentProviderNotFoundException($id->toString());
    }
}
