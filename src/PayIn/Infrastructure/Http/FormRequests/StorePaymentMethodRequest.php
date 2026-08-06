<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\FormRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use PayIn\Application\Command\RegisterPaymentMethodCommand;
use PayIn\Domain\PaymentMethod\PaymentMethodType;
use PayIn\Domain\PaymentProvider\ProviderCode;

/**
 * Validación de entrada del endpoint POST /api/v1/payment-methods.
 *
 * El método de pago es un instrumento independiente: se registra en un
 * proveedor (que debe soportar su tipo), sin vínculo a cliente ni cuenta.
 */
final class StorePaymentMethodRequest extends FormRequest
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
            'provider_code' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]{1,31}$/'],
            'type' => ['required', 'string', Rule::enum(PaymentMethodType::class)],
            'token' => ['required', 'string', 'min:4', 'max:255'],
            'details_masked' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'provider_code.regex' => 'El provider_code no es válido (2-32 caracteres en minúsculas, números o "_").',
            'type' => 'El tipo de método de pago no está soportado.',
            'token.min' => 'El token debe tener al menos 4 caracteres.',
            'token.max' => 'El token no puede exceder 255 caracteres.',
        ];
    }

    public function toCommand(): RegisterPaymentMethodCommand
    {
        return new RegisterPaymentMethodCommand(
            providerCode: ProviderCode::fromString($this->string('provider_code')->toString()),
            type: PaymentMethodType::from($this->string('type')->toString()),
            token: $this->string('token')->toString(),
            detailsMasked: $this->string('details_masked')->toString(),
        );
    }
}
