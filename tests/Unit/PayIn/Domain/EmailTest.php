<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Domain;

use PayIn\Domain\Email;
use PayIn\Domain\Exceptions\InvalidEmailException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function test_accepts_valid_email(): void
    {
        $this->assertSame('user@example.com', Email::fromString('user@example.com')->value());
    }

    public function test_normalizes_to_lowercase(): void
    {
        $this->assertSame('user@example.com', Email::fromString('USER@Example.COM')->value());
    }

    #[DataProvider('invalidEmails')]
    public function test_rejects_invalid_email(string $email): void
    {
        $this->expectException(InvalidEmailException::class);

        Email::fromString($email);
    }

    public static function invalidEmails(): array
    {
        return [
            'sin arroba' => ['usuario.example.com'],
            'sin dominio' => ['usuario@'],
            'dominio invalido' => ['usuario@example'],
            'espacios' => ['usuario @example.com'],
            'doble arroba' => ['usuario@@example.com'],
            'vacio' => [''],
            'comillas' => ['"usuario"@example.com'],
        ];
    }

    public function test_equality_is_structural(): void
    {
        $this->assertTrue(Email::fromString('a@b.co')->equals(Email::fromString('a@b.co')));
        $this->assertFalse(Email::fromString('a@b.co')->equals(Email::fromString('c@b.co')));
    }
}
