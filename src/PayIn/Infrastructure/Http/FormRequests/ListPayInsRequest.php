<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\FormRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Contracts\PayInSearchCriteria;
use PayIn\Domain\PayIn\PayInStatus;

/**
 * Validación de filtros del endpoint GET /api/v1/payins.
 *
 * client_id permite consultar el HISTORIAL de transacciones de un cliente
 * concreto (todas las operaciones en las que participó como pagador).
 */
final class ListPayInsRequest extends FormRequest
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
            'client_id' => ['nullable', 'string', 'uuid'],
            'status' => ['nullable', 'string', Rule::enum(PayInStatus::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function toCriteria(): PayInSearchCriteria
    {
        return new PayInSearchCriteria(
            clientId: $this->filled('client_id')
                ? ClientId::fromString($this->string('client_id')->toString())
                : null,
            status: $this->filled('status')
                ? PayInStatus::from($this->string('status')->toString())
                : null,
            from: $this->filled('from')
                ? new \DateTimeImmutable($this->string('from')->toString())
                : null,
            to: $this->filled('to')
                ? new \DateTimeImmutable($this->string('to')->toString())
                : null,
            limit: $this->integer('limit', PayInSearchCriteria::DEFAULT_LIMIT),
            offset: $this->integer('offset'),
        );
    }
}
