<?php

declare(strict_types=1);

namespace PayIn\Application\UseCase;

use PayIn\Application\Command\RegisterPaymentMethodCommand;
use PayIn\Application\Exception\AccountNotFoundException;
use PayIn\Application\Exception\PaymentMethodAlreadyExistsException;
use PayIn\Application\Exception\PaymentProviderNotFoundException;
use PayIn\Application\Port\Clock;
use PayIn\Domain\Contracts\AccountRepository;
use PayIn\Domain\Contracts\PaymentMethodRepository;
use PayIn\Domain\Contracts\PaymentProviderRepository;
use PayIn\Domain\Exceptions\ProviderInactiveException;
use PayIn\Domain\PaymentMethod\PaymentMethod;
use PayIn\Domain\PaymentMethod\PaymentMethodId;

/**
 * Caso de uso: registrar un método de pago en una cuenta.
 *
 * Valida la existencia de la cuenta, resuelve el proveedor por su código,
 * exige proveedor activo y garantiza la unicidad (cuenta, token) antes de
 * persistir. La violación de la restricción UNIQUE en BD también se
 * traduce en PaymentMethodAlreadyExistsException.
 */
final readonly class RegisterPaymentMethodService
{
    public function __construct(
        private AccountRepository $accounts,
        private PaymentProviderRepository $providers,
        private PaymentMethodRepository $paymentMethods,
        private Clock $clock,
    ) {
    }

    public function register(RegisterPaymentMethodCommand $command): PaymentMethod
    {
        if (!$this->accounts->findById($command->accountId) instanceof \PayIn\Domain\Account\Account) {
            throw new AccountNotFoundException($command->accountId->toString());
        }

        $provider = $this->providers->findByCode($command->providerCode)
            ?? throw new PaymentProviderNotFoundException($command->providerCode->value());

        if (!$provider->isActive()) {
            throw new ProviderInactiveException($provider->code()->value());
        }

        if ($this->paymentMethods->existsByAccountAndToken($command->accountId, $command->token)) {
            throw new PaymentMethodAlreadyExistsException($command->accountId->toString(), $command->token);
        }

        $method = PaymentMethod::register(
            id: PaymentMethodId::generate(),
            accountId: $command->accountId,
            providerId: $provider->id(),
            type: $command->type,
            token: $command->token,
            detailsMasked: $command->detailsMasked,
            createdAt: $this->clock->now(),
        );

        $this->paymentMethods->save($method);

        return $method;
    }
}
