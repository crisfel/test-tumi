<?php

declare(strict_types=1);

namespace PayIn\Application\UseCase;

use PayIn\Application\Dto\PaymentMethodPage;
use PayIn\Application\Exception\PaymentProviderNotFoundException;
use PayIn\Domain\Contracts\PaymentMethodRepository;
use PayIn\Domain\Contracts\PaymentMethodSearchCriteria;
use PayIn\Domain\Contracts\PaymentProviderRepository;

/**
 * Caso de uso de listado de métodos de pago (catálogo global de
 * instrumentos) con filtros opcionales y paginación.
 */
final readonly class ListPaymentMethodsService
{
    public function __construct(
        private PaymentMethodRepository $paymentMethods,
        private PaymentProviderRepository $providers,
    ) {
    }

    public function execute(PaymentMethodSearchCriteria $criteria): PaymentMethodPage
    {
        $resolved = $criteria;

        if ($criteria->providerCode instanceof \PayIn\Domain\PaymentProvider\ProviderCode) {
            $provider = $this->providers->findByCode($criteria->providerCode)
                ?? throw new PaymentProviderNotFoundException($criteria->providerCode->value());

            $resolved = new PaymentMethodSearchCriteria(
                type: $criteria->type,
                providerCode: null,
                providerId: $provider->id(),
                limit: $criteria->limit,
                offset: $criteria->offset,
            );
        }

        return new PaymentMethodPage(
            items: $this->paymentMethods->matching($resolved),
            total: $this->paymentMethods->countMatching($resolved),
            limit: $criteria->limit,
            offset: $criteria->offset,
        );
    }
}
