<?php

declare(strict_types=1);

namespace PayIn\Domain\Exceptions;

use PayIn\Shared\Kernel\Exceptions\DomainException;

/**
 * Base de todas las excepciones del dominio PayIn.
 */
abstract class PayInDomainException extends DomainException
{
}
