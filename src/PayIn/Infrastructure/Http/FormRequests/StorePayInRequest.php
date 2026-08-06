<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\FormRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use PayIn\Application\Command\ProcessPayInCommand;
use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Currency;
use PayIn\Domain\Money;
use PayIn\Domain\PayIn\Reference;
use PayIn\Domain\PaymentMethod\PaymentMethodId;

/**
 * Validación de entrada del endpoint POST /api/v1/payins.
 *
 * Los datos inválidos nunca llegan al dominio: la validación ocurre en la
 * capa HTTP y el comando se construye únicamente con Value Objects.
 */
final class StorePayInRequest extends FormRequest
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
            'origin_account_id' => ['required', 'string', 'uuid'],
            'account_id' => ['required', 'string', 'uuid'],
            'payment_method_id' => ['required', 'string', 'uuid'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000000'],
            'currency' => ['required', 'string', Rule::enum(Currency::class)],
            'reference' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_-]{4,64}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_id.uuid' => 'El campo client_id debe ser un UUID válido.',
            'origin_account_id.uuid' => 'El campo origin_account_id debe ser un UUID válido.',
            'account_id.uuid' => 'El campo account_id debe ser un UUID válido.',
            'payment_method_id.uuid' => 'El campo payment_method_id debe ser un UUID válido.',
            'amount.min' => 'El monto debe ser mayor a cero (unidades menores).',
            'amount.integer' => 'El monto debe expresarse en unidades menores enteras.',
            'currency' => 'La moneda no está soportada por la plataforma.',
            'reference.regex' => 'La referencia debe tener entre 4 y 64 caracteres alfanuméricos, "_" o "-".',
        ];
    }

    public function toCommand(): ProcessPayInCommand
    {
        return new ProcessPayInCommand(
            clientId: ClientId::fromString($this->string('client_id')->toString()),
            originAccountId: AccountId::fromString($this->string('origin_account_id')->toString()),
            accountId: AccountId::fromString($this->string('account_id')->toString()),
            paymentMethodId: PaymentMethodId::fromString($this->string('payment_method_id')->toString()),
            amount: Money::fromMinorUnits(
                $this->integer('amount'),
                Currency::fromCode($this->string('currency')->toString()),
            ),
            reference: $this->filled('reference')
                ? Reference::fromString($this->string('reference')->toString())
                : null,
        );
    }
}
