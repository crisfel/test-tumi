<?php

declare(strict_types=1);

namespace PayIn\Domain\PayIn;

use PayIn\Domain\Account\Account;
use PayIn\Domain\Exceptions\CurrencyMismatchException;
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
 * Encapsula las invariantes de negocio. La cuenta destino y el método de
 * pago son INDEPENDIENTES de pertenencias: el PayIn abona a cualquier
 * cuenta (de quien sea) usando cualquier método registrado y activo, cuyo
 * proveedor esté activo y soporte el tipo del método.
 */
final class PayInValidator
{
    public function validate(
        PayIn $payIn,
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

        if (!$account->currencyMatches($payIn->amount())) {
            throw new CurrencyMismatchException($account->currency(), $payIn->amount()->currency());
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
