<?php

declare(strict_types=1);

namespace PayIn\Application\UseCase;

use PayIn\Application\Command\ProcessPayInCommand;
use PayIn\Application\Dto\ChargeRequest;
use PayIn\Application\Dto\ProcessingContext;
use PayIn\Application\Dto\ProcessPayInResponse;
use PayIn\Application\Exception\AccountNotFoundException;
use PayIn\Application\Exception\ClientNotFoundException;
use PayIn\Application\Exception\PayInProcessingException;
use PayIn\Application\Exception\PaymentGatewayNotFoundException;
use PayIn\Application\Exception\PaymentMethodNotFoundException;
use PayIn\Application\Exception\PaymentProviderNotFoundException;
use PayIn\Application\Exception\ReferenceAlreadyUsedException;
use PayIn\Application\Port\Clock;
use PayIn\Application\Port\EventBus;
use PayIn\Application\Port\Logger;
use PayIn\Application\Port\PaymentGatewayRegistry;
use PayIn\Application\Port\TransactionManager;
use PayIn\Application\Result\ChargeResult;
use PayIn\Domain\Contracts\AccountRepository;
use PayIn\Domain\Contracts\ClientRepository;
use PayIn\Domain\Contracts\PayInRepository;
use PayIn\Domain\Contracts\PaymentMethodRepository;
use PayIn\Domain\Contracts\PaymentProviderRepository;
use PayIn\Domain\Money;
use PayIn\Domain\PayIn\PayIn;
use PayIn\Domain\PayIn\PayInValidator;
use PayIn\Domain\PayIn\ProviderResponse;
use PayIn\Domain\PayIn\ProviderTransactionId;
use PayIn\Domain\PayIn\TransactionId;

/**
 * Orquestador del proceso PayIn (Application Service).
 *
 * Flujo:
 *  1. Verificación de idempotencia por referencia.
 *  2. Transacción A: carga de aggregates, validación de dominio,
 *     creación del PayIn (CREATED) y persistencia inicial (VALIDATED).
 *  3. Resolución del proveedor (Strategy) y cobro FUERA de la transacción
 *     para no retener locks mientras el proveedor responde.
 *  4. Transacción B: aplicación del resultado (PROCESSED/FAILED), abono a
 *     la cuenta en caso de éxito y persistencia final.
 *
 * El proveedor se invoca fuera de cualquier transacción; los eventos de
 * dominio se despachan siempre después de cada commit.
 */
final readonly class ProcessPayInService
{
    public function __construct(
        private ClientRepository $clients,
        private AccountRepository $accounts,
        private PaymentMethodRepository $paymentMethods,
        private PaymentProviderRepository $providers,
        private PayInRepository $payIns,
        private PayInValidator $validator,
        private PaymentGatewayRegistry $gateways,
        private TransactionManager $transactions,
        private EventBus $events,
        private Clock $clock,
        private Logger $logger,
    ) {
    }

    public function process(ProcessPayInCommand $command): ProcessPayInResponse
    {
        $this->assertReferenceIsFree($command);

        $context = $this->transactions->execute(
            fn (): ProcessingContext => $this->initialize($command),
        );
        $this->dispatch($context->payIn);

        try {
            $chargeResult = $this->charge($context);
        } catch (\Throwable $exception) {
            $this->transactions->execute(
                fn (): ProcessPayInResponse => $this->finalize(
                    $context,
                    ChargeResult::error('PROVIDER_UNEXPECTED_ERROR', $exception->getMessage()),
                ),
            );
            $this->dispatch($context->payIn);
            $this->logger->error('payin.process.unexpected_provider_error', [
                'payin_id' => $context->payIn->id()->toString(),
                'provider' => $context->provider->code()->value(),
                'reason' => $exception->getMessage(),
            ]);

            throw new PayInProcessingException(
                $context->payIn->id()->toString(),
                $context->provider->code()->value(),
                $exception->getMessage(),
            );
        }

        $response = $this->transactions->execute(
            fn (): ProcessPayInResponse => $this->finalize($context, $chargeResult),
        );
        $this->dispatch($context->payIn);

        $this->logger->info('payin.process.finished', [
            'payin_id' => $context->payIn->id()->toString(),
            'status' => $response->status->value,
            'provider_transaction_id' => $response->providerTransactionId,
        ]);

        return $response;
    }

    private function initialize(ProcessPayInCommand $command): ProcessingContext
    {
        $client = $this->clients->findById($command->clientId)
            ?? throw new ClientNotFoundException($command->clientId->toString());

        $account = $this->accounts->findById($command->accountId)
            ?? throw new AccountNotFoundException($command->accountId->toString());

        $paymentMethod = $this->paymentMethods->findById($command->paymentMethodId)
            ?? throw new PaymentMethodNotFoundException($command->paymentMethodId->toString());

        $provider = $this->providers->findById($paymentMethod->providerId())
            ?? throw new PaymentProviderNotFoundException($paymentMethod->providerId()->toString());

        $payIn = PayIn::create(
            id: TransactionId::generate(),
            clientId: $client->id(),
            accountId: $account->id(),
            paymentMethodId: $paymentMethod->id(),
            amount: $command->amount,
            fees: Money::zero($command->amount->currency()),
            reference: $command->reference,
            createdAt: $this->clock->now(),
        );

        $this->validator->validate($payIn, $client, $account, $paymentMethod, $provider);

        $this->payIns->save($payIn);
        $payIn->markValidated();
        $this->payIns->save($payIn);

        $this->logger->info('payin.process.initialized', [
            'payin_id' => $payIn->id()->toString(),
            'client_id' => $client->id()->toString(),
            'account_id' => $account->id()->toString(),
            'payment_method_id' => $paymentMethod->id()->toString(),
            'amount' => $payIn->amount()->minorUnits(),
            'currency' => $payIn->amount()->currency()->value,
            'reference' => $payIn->reference()?->value(),
            'status' => $payIn->status()->value,
        ]);

        return new ProcessingContext($payIn, $account, $paymentMethod, $provider);
    }

    /**
     * @throws PaymentGatewayNotFoundException si el proveedor no tiene adapter
     * @throws \RuntimeException si el adapter falla de forma inesperada
     */
    private function charge(ProcessingContext $context): ChargeResult
    {
        $gateway = $this->gateways->resolve($context->provider);
        $this->logger->info('payin.provider.resolved', [
            'payin_id' => $context->payIn->id()->toString(),
            'provider' => $context->provider->code()->value(),
            'gateway' => $gateway::class,
        ]);

        $request = new ChargeRequest(
            payInId: $context->payIn->id(),
            clientId: $context->payIn->clientId(),
            accountId: $context->payIn->accountId(),
            paymentMethodId: $context->payIn->paymentMethodId(),
            amount: $context->payIn->amount(),
            reference: $context->payIn->reference(),
            methodType: $context->paymentMethod->type(),
            methodToken: $context->paymentMethod->token(),
            providerCode: $context->provider->code(),
        );

        $startedAt = hrtime(true);
        $result = $gateway->charge($request);
        $elapsedMs = (int) ((hrtime(true) - $startedAt) / 1_000_000);

        $this->logger->info('payin.provider.response', [
            'payin_id' => $context->payIn->id()->toString(),
            'provider' => $context->provider->code()->value(),
            'outcome' => $result->outcome->value,
            'provider_transaction_id' => $result->providerTransactionId,
            'error_code' => $result->errorCode,
            'latency_ms' => $elapsedMs,
        ]);

        return $result;
    }

    private function finalize(ProcessingContext $context, ChargeResult $chargeResult): ProcessPayInResponse
    {
        $payIn = $context->payIn;
        $payIn->markProcessing();

        if ($chargeResult->isSuccess()) {
            $payIn->markProcessed(
                providerId: $context->provider->id(),
                providerTransactionId: ProviderTransactionId::fromString((string) $chargeResult->providerTransactionId),
                providerResponse: ProviderResponse::fromArray($chargeResult->payload),
                processedAt: $this->clock->now(),
            );

            $account = $this->accounts->findById($context->payIn->accountId());
            if ($account instanceof \PayIn\Domain\Account\Account) {
                $account->credit($payIn->amount());
                $this->accounts->save($account);
            }
        } else {
            $payIn->markFailed(
                errorCode: $chargeResult->errorCode ?? $chargeResult->outcome->value,
                errorMessage: $chargeResult->message,
            );
        }

        $this->payIns->save($payIn);

        $this->logger->info('payin.process.persisted', [
            'payin_id' => $payIn->id()->toString(),
            'status' => $payIn->status()->value,
        ]);

        return ProcessPayInResponse::fromPayIn($payIn);
    }

    private function assertReferenceIsFree(ProcessPayInCommand $command): void
    {
        if ($command->reference instanceof \PayIn\Domain\PayIn\Reference && $this->payIns->existsByReference($command->reference)) {
            throw new ReferenceAlreadyUsedException($command->reference->value());
        }
    }

    private function dispatch(PayIn $payIn): void
    {
        $events = $payIn->releaseEvents();

        if ($events !== []) {
            $this->events->dispatch($events);
        }
    }
}
