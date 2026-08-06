<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Colección paginada de cuentas con metadatos de paginación.
 */
final class AccountCollectionResource extends ResourceCollection
{
    public $collects = AccountResource::class;

    /**
     * @param list<\PayIn\Domain\Account\Account> $items
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
