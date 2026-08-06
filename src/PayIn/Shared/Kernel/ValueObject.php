<?php

declare(strict_types=1);

namespace PayIn\Shared\Kernel;

/**
 * Contrato base de los Value Objects del dominio.
 *
 * Todos los VOs son inmutables y se comparan por valor, nunca por identidad.
 */
abstract readonly class ValueObject implements \Stringable
{
    abstract public function equals(ValueObject $other): bool;
}
