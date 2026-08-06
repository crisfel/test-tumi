<?php

declare(strict_types=1);

namespace PayIn\Application\UseCase;

use PayIn\Application\Dto\PayInPage;
use PayIn\Domain\Contracts\PayInRepository;
use PayIn\Domain\Contracts\PayInSearchCriteria;

/**
 * Caso de uso de listado de PayIns con filtros y paginación.
 */
final readonly class ListPayInsService
{
    public function __construct(private PayInRepository $payIns)
    {
    }

    public function execute(PayInSearchCriteria $criteria): PayInPage
    {
        return new PayInPage(
            items: $this->payIns->matching($criteria),
            total: $this->payIns->countMatching($criteria),
            limit: $criteria->limit,
            offset: $criteria->offset,
        );
    }
}
