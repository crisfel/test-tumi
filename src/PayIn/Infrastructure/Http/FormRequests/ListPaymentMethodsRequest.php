<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\FormRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use PayIn\Domain\Contracts\PaymentMethodSearchCriteria;
use PayIn\Domain\PaymentMethod\PaymentMethodType;
use PayIn\Domain\PaymentProvider\ProviderCode;

/**
 * Validación de filtros del endpoint GET /api/v1/payment-methods.
 */
final class ListPaymentMethodsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', Rule::enum(PaymentMethodType::class)],
            'provider_code' => ['nullable', 'string', 'regex:/^[a-z][a-z0-9_]{1,31}$/'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function toCriteria(): PaymentMethodSearchCriteria
    {
        return new PaymentMethodSearchCriteria(
            type: $this->filled('type') ? PaymentMethodType::from($this->string('type')->toString()) : null,
            providerCode: $this->filled('provider_code')
                ? ProviderCode::fromString($this->string('provider_code')->toString())
                : null,
            limit: $this->integer('limit', PaymentMethodSearchCriteria::DEFAULT_LIMIT),
            offset: $this->integer('offset'),
        );
    }
}
