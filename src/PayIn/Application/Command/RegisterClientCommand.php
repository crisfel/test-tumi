<?php

declare(strict_types=1);

namespace PayIn\Application\Command;

use PayIn\Domain\Email;

/**
 * Comando inmutable que representa la intención de registrar un cliente.
 */
final readonly class RegisterClientCommand
{
    public function __construct(
        public string $name,
        public Email $email,
    ) {
    }
}
