<?php

declare(strict_types=1);

namespace PayIn\Application\UseCase;

use PayIn\Application\Command\RegisterPaymentMethodCommand;
use PayIn\Application\Exception\PaymentMethodAlreadyExistsException;
use PayIn\Application\Exception\PaymentProviderNotFoundException;
use PayIn\Application\Port\Clock;
use PayIn\Domain\Contracts\PaymentMethodRepository;
use PayIn\Domain\Contracts\PaymentProviderRepository;
use PayIn\Domain\Exceptions\PaymentMethodTypeNotSupportedException;
use PayIn\Domain\Exceptions\ProviderInactiveException;
use PayIn\Domain\PaymentMethod\PaymentMethod;
use PayIn\Domain\PaymentMethod\PaymentMethodId;

/**
 * Caso de uso: registrar un método de pago (instrumento independiente).
 *
 * Valida la existencia y actividad del proveedor, que el proveedor soporte
 * el tipo del método (matriz de capacidades) y la unicidad del token en el
 * espacio de tokenización del proveedor. La violación de la restricción
 * UNIQUE en BD también se traduce en PaymentMethodAlreadyExistsException.
 */
final readonly class RegisterPaymentMethodService
{
    public function __construct(
        private PaymentProviderRepository $providers,
        private PaymentMethodRepository $paymentMethods,
        private Clock $clock,
    ) {
    }

    public function register(RegisterPaymentMethodCommand $command): PaymentMethod
    {
        $provider = $this->providers->findByCode($command->providerCode)
            ?? throw new PaymentProviderNotFoundException($command->providerCode->value());

        if (!$provider->isActive()) {
            throw new ProviderInactiveException($provider->code()->value());
        }

        if (!$provider->supports($command->type)) {
            throw new PaymentMethodTypeNotSupportedException(
                $command->type->value,
                $provider->code()->value(),
            );
        }

        if ($this->paymentMethods->existsByProviderAndToken($provider->id(), $command->token)) {
            throw new PaymentMethodAlreadyExistsException($provider->code()->value(), $command->token);
        }

        $method = PaymentMethod::register(
            id: PaymentMethodId::generate(),
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
