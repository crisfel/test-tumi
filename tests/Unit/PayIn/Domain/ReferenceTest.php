<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Domain;

use PayIn\Domain\Exceptions\InvalidReferenceException;
use PayIn\Domain\PayIn\Reference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ReferenceTest extends TestCase
{
    public function test_accepts_valid_reference(): void
    {
        $this->assertSame('order-2026-0001', Reference::fromString('order-2026-0001')->value());
    }

    #[DataProvider('invalidReferences')]
    public function test_rejects_invalid_reference(string $value): void
    {
        $this->expectException(InvalidReferenceException::class);

        Reference::fromString($value);
    }

    public static function invalidReferences(): array
    {
        return [
            'demasiado corto' => ['abc'],
            'demasiado largo' => [str_repeat('a', 65)],
            'caracteres invalidos' => ['order#2026'],
            'espacios' => ['order 2026'],
            'vacio' => [''],
            'acentos' => ['órden-2026'],
        ];
    }

    public function test_equality_is_structural(): void
    {
        $this->assertTrue(Reference::fromString('ref-1')->equals(Reference::fromString('ref-1')));
        $this->assertFalse(Reference::fromString('ref-1')->equals(Reference::fromString('ref-2')));
    }
}
