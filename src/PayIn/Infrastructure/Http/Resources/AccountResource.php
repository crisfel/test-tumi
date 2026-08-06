<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PayIn\Domain\Account\Account;

/**
 * Representación pública de una cuenta.
 *
 * Nunca expone modelos internos; sólo los datos públicos de la cuenta.
 */
final class AccountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Account $account */
        $account = $this->resource;

        return [
            'id' => $account->id()->toString(),
            'client_id' => $account->clientId()->toString(),
            'currency' => $account->currency()->value,
            'balance' => $account->balance()->minorUnits(),
        ];
    }
}
