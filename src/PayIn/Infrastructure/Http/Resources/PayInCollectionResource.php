<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Colección paginada de PayIns con metadatos de paginación.
 */
final class PayInCollectionResource extends ResourceCollection
{
    public $collects = PayInResource::class;

    /**
     * @param list<\PayIn\Domain\PayIn\PayIn> $items
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
