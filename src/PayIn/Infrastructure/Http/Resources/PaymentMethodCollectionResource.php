<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Colección paginada de métodos de pago con metadatos de paginación.
 */
final class PaymentMethodCollectionResource extends ResourceCollection
{
    public $collects = PaymentMethodResource::class;

    /**
     * @param list<\PayIn\Domain\PaymentMethod\PaymentMethod> $items
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
