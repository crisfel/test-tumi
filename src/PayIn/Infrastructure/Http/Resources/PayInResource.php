<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PayIn\Domain\PayIn\PayIn;

/**
 * Representación pública de un PayIn.
 *
 * Nunca expone modelos internos ni datos sensibles del método de pago:
 * sólo el estado y los metadatos financieros de la operación.
 */
final class PayInResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PayIn $payIn */
        $payIn = $this->resource;

        return [
            'id' => $payIn->id()->toString(),
            'client_id' => $payIn->clientId()->toString(),
            'account_id' => $payIn->accountId()->toString(),
            'payment_method_id' => $payIn->paymentMethodId()->toString(),
            'amount' => $payIn->amount()->minorUnits(),
            'currency' => $payIn->amount()->currency()->value,
            'status' => $payIn->status()->value,
            'reference' => $payIn->reference()?->value(),
            'provider_id' => $payIn->providerId()?->toString(),
            'provider_transaction_id' => $payIn->providerTransactionId()?->value(),
            'error_code' => $payIn->errorCode(),
            'error_message' => $payIn->errorMessage(),
            'created_at' => $payIn->createdAt()->format('Y-m-d\TH:i:s\Z'),
            'processed_at' => $payIn->processedAt()?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
