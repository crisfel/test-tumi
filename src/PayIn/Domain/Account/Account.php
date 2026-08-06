<?php

declare(strict_types=1);

namespace PayIn\Domain\Account;

use PayIn\Domain\Client\Client;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Currency;
use PayIn\Domain\Exceptions\CurrencyMismatchException;
use PayIn\Domain\Money;

/**
 * Aggregate Root: Cuenta financiera de un cliente.
 *
 * Centraliza los fondos de una moneda. Recibe abonos (credit) cuando un
 * PayIn se procesa exitosamente. El saldo se expresa con Money (enteros).
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

    public static function open(AccountId $id, ClientId $clientId, Currency $currency): self
    {
        return new self($id, $clientId, $currency, Money::zero($currency));
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
