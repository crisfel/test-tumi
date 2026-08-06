<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use PayIn\Application\UseCase\ListPaymentMethodsService;
use PayIn\Application\UseCase\QueryPaymentMethodService;
use PayIn\Application\UseCase\RegisterPaymentMethodService;
use PayIn\Domain\PaymentMethod\PaymentMethodId;
use PayIn\Infrastructure\Http\FormRequests\ListPaymentMethodsRequest;
use PayIn\Infrastructure\Http\FormRequests\StorePaymentMethodRequest;
use PayIn\Infrastructure\Http\Resources\PaymentMethodCollectionResource;
use PayIn\Infrastructure\Http\Resources\PaymentMethodResource;

/**
 * Endpoints de métodos de pago (API v1).
 *
 * Controlador "delgado": valida la entrada, delega en los casos de uso y
 * serializa la respuesta. No contiene lógica de negocio.
 */
final readonly class PaymentMethodController
{
    public function __construct(
        private RegisterPaymentMethodService $registerPaymentMethod,
        private QueryPaymentMethodService $queryPaymentMethod,
        private ListPaymentMethodsService $listPaymentMethods,
    ) {
    }

    /**
     * Registra un método de pago en una cuenta.
     *
     * El proveedor se resuelve por su código y debe estar activo. Un token
     * no puede registrarse dos veces en la misma cuenta (409).
     */
    #[OA\Post(
        path: '/v1/payment-methods',
        summary: 'Registrar un método de pago',
        tags: ['Payment Methods'],
        parameters: [
            new OA\Parameter(name: 'X-Correlation-Id', in: 'header', required: false, description: 'Identificador de correlación para trazabilidad', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StorePaymentMethodRequest'),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Método de pago registrado',
                content: new OA\JsonContent(ref: '#/components/schemas/PaymentMethodResponse'),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos de entrada inválidos o proveedor inactivo',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 404,
                description: 'Cuenta o proveedor inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 409,
                description: 'El token ya está registrado en la cuenta',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function store(StorePaymentMethodRequest $request): JsonResponse
    {
        $method = $this->registerPaymentMethod->register($request->toCommand());

        return (new PaymentMethodResource($method))->response()->setStatusCode(201);
    }

    /**
     * Consulta un método de pago por su identificador.
     */
    #[OA\Get(
        path: '/v1/payment-methods/{id}',
        summary: 'Consultar un método de pago',
        tags: ['Payment Methods'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'UUID del método de pago', schema: new OA\Schema(type: 'string', format: 'uuid', example: '019fd715-ec43-784b-97dd-9b2fe70bfe69')),
            new OA\Parameter(name: 'X-Correlation-Id', in: 'header', required: false, description: 'Identificador de correlación para trazabilidad', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Método de pago encontrado',
                content: new OA\JsonContent(ref: '#/components/schemas/PaymentMethodResponse'),
            ),
            new OA\Response(
                response: 404,
                description: 'Método de pago inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function show(Request $request, string $id): PaymentMethodResource
    {
        $method = $this->queryPaymentMethod->findByIdOrFail(PaymentMethodId::fromString($id));

        return new PaymentMethodResource($method);
    }

    /**
     * Lista los métodos de pago de una cuenta con paginación.
     */
    #[OA\Get(
        path: '/v1/payment-methods',
        summary: 'Listar métodos de pago de una cuenta',
        tags: ['Payment Methods'],
        parameters: [
            new OA\Parameter(name: 'account_id', in: 'query', required: true, description: 'UUID de la cuenta', schema: new OA\Schema(type: 'string', format: 'uuid', example: '019fd715-ec1a-7a7e-ab6f-f497aa52abe4')),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', maximum: 100, default: 20)),
            new OA\Parameter(name: 'offset', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 0)),
            new OA\Parameter(name: 'X-Correlation-Id', in: 'header', required: false, description: 'Identificador de correlación para trazabilidad', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Página de métodos de pago de la cuenta',
                content: new OA\JsonContent(ref: '#/components/schemas/PaymentMethodPage'),
            ),
            new OA\Response(
                response: 404,
                description: 'Cuenta inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function index(ListPaymentMethodsRequest $request): PaymentMethodCollectionResource
    {
        $page = $this->listPaymentMethods->execute($request->toCriteria());

        return new PaymentMethodCollectionResource($page->items, $page->total, $page->limit, $page->offset);
    }
}
