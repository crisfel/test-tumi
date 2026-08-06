<?php

declare(strict_types=1);

namespace PayIn\Application\Exception;

use PayIn\Shared\Kernel\Exceptions\DomainException;

/**
 * Base de las excepciones del Application layer (errores de caso de uso).
 */
abstract class PayInApplicationException extends DomainException
{
}
