<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\FormRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use PayIn\Application\Command\OpenAccountCommand;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Currency;
use PayIn\Domain\Money;

/**
 * Validación de entrada del endpoint POST /api/v1/accounts.
 */
final class StoreAccountRequest extends FormRequest
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
            'currency' => ['required', 'string', Rule::enum(Currency::class)],
            'initial_balance' => ['nullable', 'integer', 'min:0', 'max:1000000000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_id.uuid' => 'El campo client_id debe ser un UUID válido.',
            'currency' => 'La moneda no está soportada por la plataforma.',
            'initial_balance.min' => 'El saldo inicial no puede ser negativo.',
        ];
    }

    public function toCommand(): OpenAccountCommand
    {
        $currency = Currency::fromCode($this->string('currency')->toString());

        return new OpenAccountCommand(
            clientId: ClientId::fromString($this->string('client_id')->toString()),
            currency: $currency,
            initialBalance: $this->filled('initial_balance')
                ? Money::fromMinorUnits($this->integer('initial_balance'), $currency)
                : null,
        );
    }
}
