<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use PayIn\Application\UseCase\RegisterClientService;
use PayIn\Infrastructure\Http\FormRequests\StoreClientRequest;
use PayIn\Infrastructure\Http\Resources\ClientResource;

/**
 * Endpoints de clientes (API v1).
 *
 * Controlador "delgado": valida la entrada, delega en el caso de uso y
 * serializa la respuesta. No contiene lógica de negocio.
 */
final readonly class ClientController
{
    public function __construct(private RegisterClientService $registerClient)
    {
    }

    /**
     * Registra un nuevo cliente.
     *
     * El email es único en la plataforma (409 si ya existe). Un cliente
     * nuevo no posee cuentas; debe asociársele una antes de procesar PayIns.
     */
    #[OA\Post(
        path: '/v1/clients',
        summary: 'Registrar un cliente',
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(name: 'X-Correlation-Id', in: 'header', required: false, description: 'Identificador de correlación para trazabilidad', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreClientRequest'),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Cliente registrado',
                content: new OA\JsonContent(ref: '#/components/schemas/ClientResponse'),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos de entrada inválidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 409,
                description: 'Email ya registrado',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = $this->registerClient->register($request->toCommand());

        return (new ClientResource($client))->response()->setStatusCode(201);
    }
}
