<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\FormRequests;

use Illuminate\Foundation\Http\FormRequest;
use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Contracts\AccountMovementSearchCriteria;

/**
 * Validación de filtros del endpoint GET /api/v1/accounts/{id}/movements.
 */
final class ListAccountMovementsRequest extends FormRequest
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
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function toCriteria(string $accountId): AccountMovementSearchCriteria
    {
        return new AccountMovementSearchCriteria(
            accountId: AccountId::fromString($accountId),
            limit: $this->integer('limit', AccountMovementSearchCriteria::DEFAULT_LIMIT),
            offset: $this->integer('offset'),
        );
    }
}
