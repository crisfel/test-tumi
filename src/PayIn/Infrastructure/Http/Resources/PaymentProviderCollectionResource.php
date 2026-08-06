<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Colección paginada de proveedores de pago con metadatos de paginación.
 */
final class PaymentProviderCollectionResource extends ResourceCollection
{
    public $collects = PaymentProviderResource::class;

    /**
     * @param list<\PayIn\Domain\PaymentProvider\PaymentProvider> $items
     */
    public function __construct(array $items, int $total, int $limit, int $offset)
    {
        parent::__construct($items);

        $this->additional([
            'meta' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);
    }
}
