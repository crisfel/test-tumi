<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Colección paginada de movimientos del libro mayor.
 */
final class AccountMovementCollectionResource extends ResourceCollection
{
    public $collects = AccountMovementResource::class;

    /**
     * @param list<\PayIn\Domain\Account\AccountMovement> $items
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
