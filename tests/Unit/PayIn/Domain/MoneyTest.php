<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Domain;

use PayIn\Domain\Currency;
use PayIn\Domain\Exceptions\CurrencyMismatchException;
use PayIn\Domain\Exceptions\InvalidAmountException;
use PayIn\Domain\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_creates_money_from_minor_units(): void
    {
        $money = Money::fromMinorUnits(2500, Currency::COP);

        $this->assertSame(2500, $money->minorUnits());
        $this->assertSame(Currency::COP, $money->currency());
    }

    public function test_zero_money(): void
    {
        $this->assertTrue(Money::zero(Currency::USD)->isZero());
        $this->assertFalse(Money::zero(Currency::USD)->isPositive());
    }

    public function test_positive_amount(): void
    {
        $this->assertTrue(Money::fromMinorUnits(1, Currency::USD)->isPositive());
        $this->assertFalse(Money::fromMinorUnits(0, Currency::USD)->isPositive());
    }

    public function test_rejects_negative_amount(): void
    {
        $this->expectException(InvalidAmountException::class);

        Money::fromMinorUnits(-100, Currency::COP);
    }

    public function test_adds_amounts_of_same_currency(): void
    {
        $sum = Money::fromMinorUnits(1000, Currency::COP)->add(Money::fromMinorUnits(500, Currency::COP));

        $this->assertSame(1500, $sum->minorUnits());
    }

    public function test_rejects_adding_different_currencies(): void
    {
        $this->expectException(CurrencyMismatchException::class);

        Money::fromMinorUnits(1000, Currency::COP)->add(Money::fromMinorUnits(500, Currency::USD));
    }

    public function test_equality_is_structural(): void
    {
        $a = Money::fromMinorUnits(1000, Currency::COP);
        $b = Money::fromMinorUnits(1000, Currency::COP);
        $c = Money::fromMinorUnits(1000, Currency::USD);

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_string_representation(): void
    {
        $this->assertSame('2500 COP', (string) Money::fromMinorUnits(2500, Currency::COP));
    }
}
