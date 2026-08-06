<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use PayIn\Application\Exception\AccountNotFoundException;
use PayIn\Application\Exception\ClientNotFoundException;
use PayIn\Application\Exception\PayInApplicationException;
use PayIn\Application\Exception\PayInConcurrencyException;
use PayIn\Application\Exception\PayInNotFoundException;
use PayIn\Application\Exception\PayInProcessingException;
use PayIn\Application\Exception\PaymentGatewayNotFoundException;
use PayIn\Application\Exception\PaymentMethodNotFoundException;
use PayIn\Application\Exception\PaymentProviderNotFoundException;
use PayIn\Application\Exception\ReferenceAlreadyUsedException;
use PayIn\Domain\Exceptions\InvalidStateTransitionException;
use PayIn\Domain\Exceptions\PayInDomainException;
use PayIn\Domain\Exceptions\PayInValidationException;
use PayIn\Shared\Kernel\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Traduce la jerarquía de excepciones del componente en respuestas HTTP
 * homogéneas: {errors: [{code, message, meta}]}.
 *
 * Nunca expone detalles internos: las excepciones desconocidas se dejan al
 * manejador por defecto (500 genérico, sin stack trace).
 */
final class PayInExceptionRenderer
{
    public function render(Throwable $exception): ?JsonResponse
    {
        if ($exception instanceof ValidationException) {
            return $this->json(422, 'VALIDATION_ERROR', 'Los datos proporcionados no son válidos.', [
                'fields' => $exception->errors(),
            ]);
        }

        if ($exception instanceof NotFoundHttpException) {
            return $this->json(404, 'ROUTE_NOT_FOUND', 'El recurso solicitado no existe.');
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return $this->json(405, 'METHOD_NOT_ALLOWED', 'El método HTTP no está permitido para este recurso.');
        }

        if ($exception instanceof ModelNotFoundException) {
            return $this->json(404, 'RESOURCE_NOT_FOUND', 'El recurso solicitado no existe.');
        }

        if ($exception instanceof PayInApplicationException) {
            return $this->renderApplicationException($exception);
        }

        if ($exception instanceof PayInValidationException) {
            return $this->renderDomainException($exception, 422);
        }

        if ($exception instanceof InvalidStateTransitionException) {
            return $this->renderDomainException($exception, 409);
        }

        if ($exception instanceof PayInDomainException) {
            return $this->renderDomainException($exception, 422);
        }

        if ($exception instanceof \InvalidArgumentException) {
            return $this->json(422, 'INVALID_ARGUMENT', $exception->getMessage());
        }

        return null;
    }

    private function renderApplicationException(PayInApplicationException $exception): JsonResponse
    {
        return match (true) {
            $exception instanceof ClientNotFoundException,
            $exception instanceof AccountNotFoundException,
            $exception instanceof PaymentMethodNotFoundException,
            $exception instanceof PaymentProviderNotFoundException,
            $exception instanceof PayInNotFoundException => $this->renderDomainException($exception, 404),
            $exception instanceof ReferenceAlreadyUsedException,
            $exception instanceof PayInConcurrencyException => $this->renderDomainException($exception, 409),
            $exception instanceof PaymentGatewayNotFoundException,
            $exception instanceof PayInProcessingException => $this->renderDomainException($exception, 502),
            default => $this->renderDomainException($exception, 500),
        };
    }

    private function renderDomainException(DomainException $exception, int $status): JsonResponse
    {
        return $this->json($status, $exception->errorCode(), $exception->getMessage(), $exception->context());
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function json(int $status, string $code, string $message, array $meta = []): JsonResponse
    {
        return new JsonResponse([
            'errors' => [
                [
                    'code' => $code,
                    'message' => $message,
                    'meta' => $meta,
                ],
            ],
        ], $status);
    }
}
