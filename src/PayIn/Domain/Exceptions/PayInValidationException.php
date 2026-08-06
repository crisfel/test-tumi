<?php

declare(strict_types=1);

namespace PayIn\Domain\Exceptions;

/**
 * Base de los errores de validación de elegibilidad de un PayIn.
 */
abstract class PayInValidationException extends PayInDomainException
{
}
