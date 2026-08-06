<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\FormRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use PayIn\Application\Command\AdjustAccountBalanceCommand;
use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Account\BalanceAdjustmentType;
use PayIn\Domain\Currency;
use PayIn\Domain\Money;

/**
 * Validación de entrada del endpoint PATCH /api/v1/accounts/{id}/balance.
 */
final class AdjustAccountBalanceRequest extends FormRequest
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
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000000'],
            'direction' => ['required', 'string', Rule::enum(BalanceAdjustmentType::class)],
            'currency' => ['required', 'string', Rule::enum(Currency::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.min' => 'El monto del ajuste debe ser mayor a cero (unidades menores).',
            'direction' => 'La dirección del ajuste debe ser "increase" (aumenta) o "decrease" (disminuye).',
            'currency' => 'La moneda no está soportada por la plataforma.',
        ];
    }

    public function toCommand(string $accountId): AdjustAccountBalanceCommand
    {
        return new AdjustAccountBalanceCommand(
            accountId: AccountId::fromString($accountId),
            amount: Money::fromMinorUnits(
                $this->integer('amount'),
                Currency::fromCode($this->string('currency')->toString()),
            ),
            type: BalanceAdjustmentType::from($this->string('direction')->toString()),
        );
    }
}
