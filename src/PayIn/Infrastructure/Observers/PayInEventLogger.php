<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Observers;

use Illuminate\Support\Facades\Log;
use PayIn\Domain\PayIn\Events\PayInCreated;
use PayIn\Domain\PayIn\Events\PayInFailed;
use PayIn\Domain\PayIn\Events\PayInProcessed;
use PayIn\Domain\PayIn\Events\PayInProcessing;
use PayIn\Domain\PayIn\Events\PayInValidated;
use PayIn\Shared\Kernel\DomainEvent;

/**
 * Observador de eventos de dominio del PayIn.
 *
 * Registra cada cambio de estado con contexto estructurado en el canal
 * "payin". Si en el futuro los eventos se despachan a colas o a un bus,
 * este observador puede reemplazarse sin tocar el dominio.
 */
final class PayInEventLogger
{
    public function handle(DomainEvent $event): void
    {
        Log::channel('payin')->info('payin.state.changed', $this->context($event));
    }

    /**
     * @return array<string, mixed>
     */
    private function context(DomainEvent $event): array
    {
        return match (true) {
            $event instanceof PayInCreated => ['payin_id' => $event->payInId->toString(), 'status' => 'created'],
            $event instanceof PayInValidated => ['payin_id' => $event->payInId->toString(), 'status' => 'validated'],
            $event instanceof PayInProcessing => ['payin_id' => $event->payInId->toString(), 'status' => 'processing'],
            $event instanceof PayInProcessed => [
                'payin_id' => $event->payInId->toString(),
                'status' => 'processed',
                'provider_transaction_id' => $event->providerTransactionId->value(),
            ],
            $event instanceof PayInFailed => [
                'payin_id' => $event->payInId->toString(),
                'status' => 'failed',
                'error_code' => $event->errorCode,
                'error_message' => $event->errorMessage,
            ],
            default => ['event' => $event::class],
        };
    }
}
