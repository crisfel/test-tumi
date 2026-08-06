<?php

declare(strict_types=1);

namespace PayIn\Application\UseCase;

use PayIn\Application\Dto\PaymentMethodPage;
use PayIn\Application\Exception\AccountNotFoundException;
use PayIn\Domain\Contracts\AccountRepository;
use PayIn\Domain\Contracts\PaymentMethodRepository;
use PayIn\Domain\Contracts\PaymentMethodSearchCriteria;

/**
 * Caso de uso de listado de métodos de pago de una cuenta.
 *
 * Valida que la cuenta exista (404) y devuelve sus métodos paginados.
 */
final readonly class ListPaymentMethodsService
{
    public function __construct(
        private AccountRepository $accounts,
        private PaymentMethodRepository $paymentMethods,
    ) {
    }

    public function execute(PaymentMethodSearchCriteria $criteria): PaymentMethodPage
    {
        if (!$this->accounts->findById($criteria->accountId) instanceof \PayIn\Domain\Account\Account) {
            throw new AccountNotFoundException($criteria->accountId->toString());
        }

        return new PaymentMethodPage(
            items: $this->paymentMethods->matching($criteria),
            total: $this->paymentMethods->countMatching($criteria),
            limit: $criteria->limit,
            offset: $criteria->offset,
        );
    }
}
