<?php

declare(strict_types=1);

namespace PayIn\Domain\Client;

use PayIn\Domain\Email;
use PayIn\Domain\Exceptions\InvalidClientNameException;

/**
 * Aggregate Root: Cliente de la plataforma.
 *
 * Posee una o más cuentas y origina las transacciones. Sin lógica
 * dependiente de infraestructura.
 */
final readonly class Client
{
    private const MAX_NAME_LENGTH = 100;

    private function __construct(
        private ClientId $id,
        private string $name,
        private Email $email,
    ) {
    }

    public static function register(ClientId $id, string $name, Email $email): self
    {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > self::MAX_NAME_LENGTH) {
            throw new InvalidClientNameException($name);
        }

        return new self($id, $name, $email);
    }

    public static function reconstitute(ClientId $id, string $name, Email $email): self
    {
        return new self($id, $name, $email);
    }

    public function id(): ClientId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }
}
