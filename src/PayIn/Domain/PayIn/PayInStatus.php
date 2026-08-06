<?php

declare(strict_types=1);

namespace PayIn\Domain\PayIn;

/**
 * Máquina de estados del PayIn (Patrón State).
 *
 * Cada estado declara sus propias transiciones permitidas; el aggregate
 * delega en este enum la validación antes de cualquier cambio de estado.
 * Las transiciones se registran en orden y nunca se permiten saltos inválidos.
 */
enum PayInStatus: string
{
    case CREATED = 'created';

    case VALIDATED = 'validated';

    case PROCESSING = 'processing';

    case PROCESSED = 'processed';

    case FAILED = 'failed';

    /**
     * Transiciones permitidas desde este estado.
     *
     * @return list<self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::CREATED => [self::VALIDATED, self::PROCESSING, self::FAILED],
            self::VALIDATED => [self::PROCESSING, self::FAILED],
            self::PROCESSING => [self::PROCESSED, self::FAILED],
            self::PROCESSED => [],
            self::FAILED => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->transitions(), true);
    }

    public function isTerminal(): bool
    {
        return $this->transitions() === [];
    }
}
