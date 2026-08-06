<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\FormRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use PayIn\Application\Command\OpenAccountCommand;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Currency;

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
        ];
    }

    public function toCommand(): OpenAccountCommand
    {
        return new OpenAccountCommand(
            clientId: ClientId::fromString($this->string('client_id')->toString()),
            currency: Currency::fromCode($this->string('currency')->toString()),
        );
    }
}
