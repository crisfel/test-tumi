<?php

declare(strict_types=1);

namespace PayIn\Application\UseCase;

use PayIn\Application\Exception\PayInNotFoundException;
use PayIn\Domain\Contracts\PayInRepository;
use PayIn\Domain\PayIn\PayIn;
use PayIn\Domain\PayIn\TransactionId;

/**
 * Caso de uso de consulta de un PayIn por su identificador.
 */
final readonly class QueryPayInService
{
    public function __construct(private PayInRepository $payIns)
    {
    }

    public function findById(TransactionId $id): ?PayIn
    {
        return $this->payIns->findById($id);
    }

    /**
     * @throws PayInNotFoundException
     */
    public function findByIdOrFail(TransactionId $id): PayIn
    {
        return $this->payIns->findById($id)
            ?? throw new PayInNotFoundException($id->toString());
    }
}
