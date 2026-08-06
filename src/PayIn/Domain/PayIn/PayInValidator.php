<?php

declare(strict_types=1);

namespace PayIn\Domain\PayIn;

use PayIn\Domain\Account\Account;
use PayIn\Domain\Client\Client;
use PayIn\Domain\Exceptions\AccountNotBelongingToClientException;
use PayIn\Domain\Exceptions\CurrencyMismatchException;
use PayIn\Domain\Exceptions\InsufficientFundsException;
use PayIn\Domain\Exceptions\PayInAmountInvalidException;
use PayIn\Domain\Exceptions\PaymentMethodInactiveException;
use PayIn\Domain\Exceptions\PaymentMethodTypeNotSupportedException;
use PayIn\Domain\Exceptions\ProviderInactiveException;
use PayIn\Domain\PaymentMethod\PaymentMethod;
use PayIn\Domain\PaymentProvider\PaymentProvider;

/**
 * Domain Service: valida la elegibilidad de un PayIn antes de su
 * procesamiento.
 *
 * El PayIn mueve fondos: se DEBITA la cuenta de origen (del cliente que
 * paga) y se ACREDITA la cuenta destino (de quien sea). Invariantes:
 * origen pertenece al cliente, ambas monedas coinciden con el monto, el
 * origen tiene saldo suficiente, el método está activo y su proveedor
 * soporta el tipo.
 */
final class PayInValidator
{
    public function validate(
        PayIn $payIn,
        Client $client,
        Account $originAccount,
        Account $destinationAccount,
        PaymentMethod $paymentMethod,
        PaymentProvider $provider,
    ): void {
        if (!$payIn->amount()->isPositive()) {
            throw new PayInAmountInvalidException($payIn->amount()->minorUnits());
        }

        if (!$originAccount->belongsToClient($client)) {
            throw new AccountNotBelongingToClientException(
                $originAccount->id()->toString(),
                $payIn->id()->toString(),
            );
        }

        if (!$originAccount->currencyMatches($payIn->amount())) {
            throw new CurrencyMismatchException($originAccount->currency(), $payIn->amount()->currency());
        }

        if (!$destinationAccount->currencyMatches($payIn->amount())) {
            throw new CurrencyMismatchException($destinationAccount->currency(), $payIn->amount()->currency());
        }

        if (!$originAccount->hasSufficientFunds($payIn->amount())) {
            throw new InsufficientFundsException(
                $originAccount->balance()->minorUnits(),
                $payIn->amount()->minorUnits(),
            );
        }

        if (!$payIn->fees()->isZero() && !$originAccount->currencyMatches($payIn->fees())) {
            throw new CurrencyMismatchException($originAccount->currency(), $payIn->fees()->currency());
        }

        if (!$paymentMethod->isActive()) {
            throw new PaymentMethodInactiveException($payIn->id()->toString());
        }

        if (!$provider->isActive()) {
            throw new ProviderInactiveException($provider->code()->value(), $payIn->id()->toString());
        }

        if (!$provider->supports($paymentMethod->type())) {
            throw new PaymentMethodTypeNotSupportedException(
                $paymentMethod->type()->value,
                $provider->code()->value(),
            );
        }
    }
}
