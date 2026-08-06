<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PayIn\Domain\PaymentProvider\PaymentProvider;

/**
 * Representación pública de un proveedor de pago con su matriz de
 * capacidades (tipos de método que soporta).
 */
final class PaymentProviderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PaymentProvider $provider */
        $provider = $this->resource;

        return [
            'id' => $provider->id()->toString(),
            'code' => $provider->code()->value(),
            'name' => $provider->name(),
            'is_active' => $provider->isActive(),
            'supported_types' => array_map(
                static fn (\PayIn\Domain\PaymentMethod\PaymentMethodType $type): string => $type->value,
                $provider->supportedTypes(),
            ),
        ];
    }
}
