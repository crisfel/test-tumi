<?php

declare(strict_types=1);

namespace PayIn\Domain\Account;

use PayIn\Domain\Client\Client;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Currency;
use PayIn\Domain\Exceptions\CurrencyMismatchException;
use PayIn\Domain\Exceptions\InsufficientFundsException;
use PayIn\Domain\Money;

/**
 * Aggregate Root: Cuenta financiera de un cliente.
 *
 * Centraliza los fondos de una moneda. Recibe abonos (credit) y sufre
 * débitos (debit) cuando un PayIn la usa como origen o destino; el saldo
 * nunca puede ser negativo. Cada movimiento se registra en el libro mayor
 * (AccountMovement) por la capa de aplicación.
 */
final class Account
{
    private function __construct(
        private readonly AccountId $id,
        private readonly ClientId $clientId,
        private readonly Currency $currency,
        private Money $balance,
    ) {
    }

    public static function open(AccountId $id, ClientId $clientId, Currency $currency, ?Money $initialBalance = null): self
    {
        $balance = $initialBalance ?? Money::zero($currency);

        if ($balance->currency() !== $currency) {
            throw new CurrencyMismatchException($currency, $balance->currency());
        }

        return new self($id, $clientId, $currency, $balance);
    }

    public static function reconstitute(AccountId $id, ClientId $clientId, Currency $currency, Money $balance): self
    {
        return new self($id, $clientId, $currency, $balance);
    }

    public function belongsToClient(Client $client): bool
    {
        return $this->clientId->equals($client->id());
    }

    public function currencyMatches(Money $money): bool
    {
        return $this->currency === $money->currency();
    }

    public function credit(Money $amount): void
    {
        if (!$this->currencyMatches($amount)) {
            throw new CurrencyMismatchException($this->currency, $amount->currency());
        }

        $this->balance = $this->balance->add($amount);
    }

    /**
     * @throws InsufficientFundsException si el saldo no alcanza para el débito
     */
    public function debit(Money $amount): void
    {
        if (!$this->currencyMatches($amount)) {
            throw new CurrencyMismatchException($this->currency, $amount->currency());
        }

        if (!$this->hasSufficientFunds($amount)) {
            throw new InsufficientFundsException(
                $this->balance->minorUnits(),
                $amount->minorUnits(),
            );
        }

        $this->balance = $this->balance->subtract($amount);
    }

    public function hasSufficientFunds(Money $amount): bool
    {
        return $this->currencyMatches($amount) && $this->balance->isGreaterThanOrEqual($amount);
    }

    public function id(): AccountId
    {
        return $this->id;
    }

    public function clientId(): ClientId
    {
        return $this->clientId;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function balance(): Money
    {
        return $this->balance;
    }
}
