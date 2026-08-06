<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Domain;

use PayIn\Domain\Account\Account;
use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Client\Client;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Currency;
use PayIn\Domain\Email;
use PayIn\Domain\Exceptions\CurrencyMismatchException;
use PayIn\Domain\Exceptions\InsufficientFundsException;
use PayIn\Domain\Money;
use PHPUnit\Framework\TestCase;

final class AccountTest extends TestCase
{
    public function test_opening_account_starts_with_zero_balance(): void
    {
        $account = Account::open(AccountId::generate(), ClientId::generate(), Currency::USD);

        $this->assertTrue($account->balance()->isZero());
    }

    public function test_opening_account_with_initial_balance(): void
    {
        $account = Account::open(
            AccountId::generate(),
            ClientId::generate(),
            Currency::COP,
            Money::fromMinorUnits(10000, Currency::COP),
        );

        $this->assertSame(10000, $account->balance()->minorUnits());
    }

    public function test_rejects_initial_balance_in_other_currency(): void
    {
        $this->expectException(CurrencyMismatchException::class);

        Account::open(
            AccountId::generate(),
            ClientId::generate(),
            Currency::COP,
            Money::fromMinorUnits(10000, Currency::USD),
        );
    }

    public function test_credit_increases_balance(): void
    {
        $account = Account::open(AccountId::generate(), ClientId::generate(), Currency::COP);
        $account->credit(Money::fromMinorUnits(15000, Currency::COP));
        $account->credit(Money::fromMinorUnits(5000, Currency::COP));

        $this->assertSame(20000, $account->balance()->minorUnits());
    }

    public function test_credit_rejects_different_currency(): void
    {
        $account = Account::open(AccountId::generate(), ClientId::generate(), Currency::COP);

        $this->expectException(CurrencyMismatchException::class);

        $account->credit(Money::fromMinorUnits(15000, Currency::USD));
    }

    public function test_debit_decreases_balance(): void
    {
        $account = Account::open(
            AccountId::generate(),
            ClientId::generate(),
            Currency::COP,
            Money::fromMinorUnits(10000, Currency::COP),
        );

        $account->debit(Money::fromMinorUnits(2000, Currency::COP));

        $this->assertSame(8000, $account->balance()->minorUnits());
    }

    public function test_debit_rejects_different_currency(): void
    {
        $account = Account::open(
            AccountId::generate(),
            ClientId::generate(),
            Currency::COP,
            Money::fromMinorUnits(10000, Currency::COP),
        );

        $this->expectException(CurrencyMismatchException::class);

        $account->debit(Money::fromMinorUnits(2000, Currency::USD));
    }

    public function test_debit_rejects_insufficient_funds(): void
    {
        $account = Account::open(
            AccountId::generate(),
            ClientId::generate(),
            Currency::COP,
            Money::fromMinorUnits(10000, Currency::COP),
        );

        $this->expectException(InsufficientFundsException::class);

        $account->debit(Money::fromMinorUnits(20000, Currency::COP));
    }

    public function test_has_sufficient_funds(): void
    {
        $account = Account::open(
            AccountId::generate(),
            ClientId::generate(),
            Currency::COP,
            Money::fromMinorUnits(10000, Currency::COP),
        );

        $this->assertTrue($account->hasSufficientFunds(Money::fromMinorUnits(10000, Currency::COP)));
        $this->assertFalse($account->hasSufficientFunds(Money::fromMinorUnits(10001, Currency::COP)));
        $this->assertFalse($account->hasSufficientFunds(Money::fromMinorUnits(100, Currency::USD)));
    }

    public function test_account_belongs_to_client(): void
    {
        $clientId = ClientId::generate();
        $client = Client::register($clientId, 'Ana García', Email::fromString('ana@example.com'));
        $account = Account::open(AccountId::generate(), $clientId, Currency::USD);

        $this->assertTrue($account->belongsToClient($client));
    }

    public function test_account_currency_match(): void
    {
        $account = Account::open(AccountId::generate(), ClientId::generate(), Currency::USD);

        $this->assertTrue($account->currencyMatches(Money::fromMinorUnits(100, Currency::USD)));
        $this->assertFalse($account->currencyMatches(Money::fromMinorUnits(100, Currency::EUR)));
    }
}
