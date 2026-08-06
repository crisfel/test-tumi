<?php

declare(strict_types=1);

namespace PayIn\Application\UseCase;

use PayIn\Application\Dto\PaymentProviderPage;
use PayIn\Domain\Contracts\PaymentProviderRepository;

/**
 * Caso de uso de listado del catálogo de proveedores de pago.
 */
final readonly class ListPaymentProvidersService
{
    public function __construct(private PaymentProviderRepository $providers)
    {
    }

    public function execute(int $limit = 20, int $offset = 0): PaymentProviderPage
    {
        $items = $this->providers->all();

        return new PaymentProviderPage(
            items: array_slice($items, $offset, $limit),
            total: count($items),
            limit: $limit,
            offset: $offset,
        );
    }
}
