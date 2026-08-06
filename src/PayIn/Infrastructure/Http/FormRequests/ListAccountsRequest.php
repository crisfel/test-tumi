<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\FormRequests;

use Illuminate\Foundation\Http\FormRequest;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Contracts\AccountSearchCriteria;

/**
 * Validación de filtros del endpoint GET /api/v1/accounts.
 */
final class ListAccountsRequest extends FormRequest
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
            'client_id' => ['required', 'string', 'uuid'],
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
            'client_id.required' => 'El parámetro client_id es obligatorio.',
            'client_id.uuid' => 'El parámetro client_id debe ser un UUID válido.',
        ];
    }

    public function toCriteria(): AccountSearchCriteria
    {
        return new AccountSearchCriteria(
            clientId: ClientId::fromString($this->string('client_id')->toString()),
            limit: $this->integer('limit', AccountSearchCriteria::DEFAULT_LIMIT),
            offset: $this->integer('offset'),
        );
    }
}
