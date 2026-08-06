<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\FormRequests;

use Illuminate\Foundation\Http\FormRequest;
use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Contracts\PaymentMethodSearchCriteria;

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
            'account_id' => ['required', 'string', 'uuid'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'account_id.required' => 'El parámetro account_id es obligatorio.',
            'account_id.uuid' => 'El parámetro account_id debe ser un UUID válido.',
        ];
    }

    public function toCriteria(): PaymentMethodSearchCriteria
    {
        return new PaymentMethodSearchCriteria(
            accountId: AccountId::fromString($this->string('account_id')->toString()),
            limit: $this->integer('limit', PaymentMethodSearchCriteria::DEFAULT_LIMIT),
            offset: $this->integer('offset'),
        );
    }
}
