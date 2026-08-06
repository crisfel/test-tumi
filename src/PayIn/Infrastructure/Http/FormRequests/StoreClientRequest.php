<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\FormRequests;

use Illuminate\Foundation\Http\FormRequest;
use PayIn\Application\Command\RegisterClientCommand;
use PayIn\Domain\Email;
use PayIn\Infrastructure\Http\Rules\EmailRule;

/**
 * Validación de entrada del endpoint POST /api/v1/clients.
 */
final class StoreClientRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:1', 'max:100'],
            'email' => ['required', 'string', 'max:255', new EmailRule()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El campo name es obligatorio.',
            'name.max' => 'El nombre no puede exceder 100 caracteres.',
            'email.required' => 'El campo email es obligatorio.',
        ];
    }

    public function toCommand(): RegisterClientCommand
    {
        return new RegisterClientCommand(
            name: $this->string('name')->toString(),
            email: Email::fromString($this->string('email')->toString()),
        );
    }
}
