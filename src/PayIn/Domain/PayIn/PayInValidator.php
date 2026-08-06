<?php

declare(strict_types=1);

namespace PayIn\Domain\PayIn;

use PayIn\Domain\Account\Account;
use PayIn\Domain\Client\Client;
use PayIn\Domain\Exceptions\AccountNotBelongingToClientException;
use PayIn\Domain\Exceptions\CurrencyMismatchException;
use PayIn\Domain\Exceptions\PayInAmountInvalidException;
use PayIn\Domain\Exceptions\PaymentMethodInactiveException;
use PayIn\Domain\Exceptions\PaymentMethodNotBelongingToAccountException;
use PayIn\Domain\Exceptions\PaymentMethodProviderMismatchException;
use PayIn\Domain\Exceptions\ProviderInactiveException;
use PayIn\Domain\PaymentMethod\PaymentMethod;
use PayIn\Domain\PaymentProvider\PaymentProvider;

/**
 * Domain Service: valida la elegibilidad de un PayIn antes de su
 * procesamiento.
 *
 * Encapsula las invariantes de negocio que cruzan varios aggregates. Al
 * invocarse antes de la persistencia, los errores se traducen directamente
 * en respuestas 4xx sin dejar operaciones huérfanas.
 */
final class PayInValidator
{
    public function validate(
        PayIn $payIn,
        Client $client,
        Account $account,
        PaymentMethod $paymentMethod,
        PaymentProvider $provider,
    ): void {
        if (!$payIn->amount()->isPositive()) {
            throw new PayInAmountInvalidException($payIn->amount()->minorUnits());
        }

        if (!$payIn->fees()->isZero() && !$account->currencyMatches($payIn->fees())) {
            throw new CurrencyMismatchException($account->currency(), $payIn->fees()->currency());
        }

        if (!$account->belongsToClient($client)) {
            throw new AccountNotBelongingToClientException($payIn->id()->toString());
        }

        if (!$account->currencyMatches($payIn->amount())) {
            throw new CurrencyMismatchException($account->currency(), $payIn->amount()->currency());
        }

        if (!$paymentMethod->belongsToAccount($account)) {
            throw new PaymentMethodNotBelongingToAccountException($payIn->id()->toString());
        }

        if (!$paymentMethod->isActive()) {
            throw new PaymentMethodInactiveException($payIn->id()->toString());
        }

        if (!$paymentMethod->usesProvider($provider)) {
            throw new PaymentMethodProviderMismatchException($payIn->id()->toString());
        }

        if (!$provider->isActive()) {
            throw new ProviderInactiveException($payIn->id()->toString(), $provider->code()->value());
        }
    }
}
