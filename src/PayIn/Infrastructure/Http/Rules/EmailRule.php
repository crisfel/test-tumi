<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use PayIn\Domain\Email;
use PayIn\Domain\Exceptions\InvalidEmailException;

/**
 * Regla de validación de email delegada al Value Object del dominio.
 *
 * Decisión de seguridad: NO se utiliza la regla "email" del framework
 * Laravel 11, cuya versión presenta la vulnerabilidad CVE-2026-48019
 * (inyección CRLF) sin corrección publicada en la rama 11.x.
 */
final class EmailRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('El campo :attribute debe ser una cadena de texto.');

            return;
        }

        try {
            Email::fromString($value);
        } catch (InvalidEmailException) {
            $fail('El campo :attribute no es una dirección de correo válida.');
        }
    }
}
