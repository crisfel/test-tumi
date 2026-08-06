<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PayIn\Domain\Account\AccountMovement;

/**
 * Representación pública de un movimiento del libro mayor (extracto).
 */
final class AccountMovementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var AccountMovement $movement */
        $movement = $this->resource;

        return [
            'id' => $movement->id()->toString(),
            'account_id' => $movement->accountId()->toString(),
            'type' => $movement->type()->value,
            'amount' => $movement->amount()->minorUnits(),
            'currency' => $movement->amount()->currency()->value,
            'balance_after' => $movement->balanceAfter()->minorUnits(),
            'pay_in_id' => $movement->payInId()?->toString(),
            'occurred_at' => $movement->occurredAt()->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
