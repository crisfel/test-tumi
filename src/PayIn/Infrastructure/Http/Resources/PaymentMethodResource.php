<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PayIn\Domain\PaymentMethod\PaymentMethod;

/**
 * Representación pública de un método de pago.
 *
 * El token de cobro NUNCA se expone: sólo se muestra la información
 * enmascarada y los metadatos de la operación.
 */
final class PaymentMethodResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PaymentMethod $method */
        $method = $this->resource;

        return [
            'id' => $method->id()->toString(),
            'account_id' => $method->accountId()->toString(),
            'provider_id' => $method->providerId()->toString(),
            'type' => $method->type()->value,
            'details_masked' => $method->detailsMasked(),
            'is_active' => $method->isActive(),
            'created_at' => $method->createdAt()->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
