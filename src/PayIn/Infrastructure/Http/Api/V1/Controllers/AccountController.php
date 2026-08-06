<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use PayIn\Application\UseCase\AdjustAccountBalanceService;
use PayIn\Application\UseCase\ListAccountMovementsService;
use PayIn\Application\UseCase\ListAccountsService;
use PayIn\Application\UseCase\OpenAccountService;
use PayIn\Application\UseCase\QueryAccountService;
use PayIn\Domain\Account\AccountId;
use PayIn\Infrastructure\Http\FormRequests\AdjustAccountBalanceRequest;
use PayIn\Infrastructure\Http\FormRequests\ListAccountMovementsRequest;
use PayIn\Infrastructure\Http\FormRequests\ListAccountsRequest;
use PayIn\Infrastructure\Http\FormRequests\StoreAccountRequest;
use PayIn\Infrastructure\Http\Resources\AccountCollectionResource;
use PayIn\Infrastructure\Http\Resources\AccountMovementCollectionResource;
use PayIn\Infrastructure\Http\Resources\AccountResource;

/**
 * Endpoints de cuentas (API v1).
 *
 * Controlador "delgado": valida la entrada, delega en los casos de uso y
 * serializa la respuesta. No contiene lógica de negocio.
 */
final readonly class AccountController
{
    public function __construct(
        private OpenAccountService $openAccount,
        private QueryAccountService $queryAccount,
        private ListAccountsService $listAccounts,
        private AdjustAccountBalanceService $adjustBalance,
        private ListAccountMovementsService $listMovements,
    ) {
    }

    /**
     * Abre una cuenta para un cliente en una moneda.
     *
     * El campo opcional initial_balance (unidades menores) asigna un saldo
     * inicial; por defecto la cuenta abre en cero. La plataforma permite
     * una única cuenta por cliente y moneda (409 si ya existe).
     */
    #[OA\Post(
        path: '/v1/accounts',
        summary: 'Abrir una cuenta',
        tags: ['Accounts'],
        parameters: [
            new OA\Parameter(name: 'X-Correlation-Id', in: 'header', required: false, description: 'Identificador de correlación para trazabilidad', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreAccountRequest'),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Cuenta abierta',
                content: new OA\JsonContent(ref: '#/components/schemas/AccountResponse'),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos de entrada inválidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 404,
                description: 'Cliente inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 409,
                description: 'El cliente ya posee una cuenta en esa moneda',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function store(StoreAccountRequest $request): JsonResponse
    {
        $account = $this->openAccount->open($request->toCommand());

        return (new AccountResource($account))->response()->setStatusCode(201);
    }

    /**
     * Consulta una cuenta por su identificador.
     */
    #[OA\Get(
        path: '/v1/accounts/{id}',
        summary: 'Consultar una cuenta',
        tags: ['Accounts'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'UUID de la cuenta', schema: new OA\Schema(type: 'string', format: 'uuid', example: '019fd715-ec1a-7a7e-ab6f-f497aa52abe4')),
            new OA\Parameter(name: 'X-Correlation-Id', in: 'header', required: false, description: 'Identificador de correlación para trazabilidad', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cuenta encontrada',
                content: new OA\JsonContent(ref: '#/components/schemas/AccountResponse'),
            ),
            new OA\Response(
                response: 404,
                description: 'Cuenta inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function show(Request $request, string $id): AccountResource
    {
        $account = $this->queryAccount->findByIdOrFail(AccountId::fromString($id));

        return new AccountResource($account);
    }

    /**
     * Lista las cuentas de un cliente con paginación.
     */
    #[OA\Get(
        path: '/v1/accounts',
        summary: 'Listar cuentas de un cliente',
        tags: ['Accounts'],
        parameters: [
            new OA\Parameter(name: 'client_id', in: 'query', required: true, description: 'UUID del cliente', schema: new OA\Schema(type: 'string', format: 'uuid', example: '019fd715-ebf8-7223-ada8-b3c168a28e22')),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', maximum: 100, default: 20)),
            new OA\Parameter(name: 'offset', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 0)),
            new OA\Parameter(name: 'X-Correlation-Id', in: 'header', required: false, description: 'Identificador de correlación para trazabilidad', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Página de cuentas del cliente',
                content: new OA\JsonContent(ref: '#/components/schemas/AccountPage'),
            ),
            new OA\Response(
                response: 404,
                description: 'Cliente inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function index(ListAccountsRequest $request): AccountCollectionResource
    {
        $page = $this->listAccounts->execute($request->toCriteria());

        return new AccountCollectionResource($page->items, $page->total, $page->limit, $page->offset);
    }

    /**
     * Ajusta el saldo de una cuenta.
     *
     * direction "increase" AUMENTA el saldo (crédito); direction "decrease"
     * DISMINUYE el saldo (débito) y requiere saldo suficiente
     * (422 INSUFFICIENT_FUNDS si no lo hay). Cada ajuste queda registrado
     * en el libro mayor de la cuenta.
     */
    #[OA\Patch(
        path: '/v1/accounts/{id}/balance',
        summary: 'Ajustar saldo de una cuenta',
        tags: ['Accounts'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'UUID de la cuenta', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'X-Correlation-Id', in: 'header', required: false, description: 'Identificador de correlación para trazabilidad', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AdjustAccountBalanceRequest'),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Saldo ajustado (cuenta actualizada)',
                content: new OA\JsonContent(ref: '#/components/schemas/AccountResponse'),
            ),
            new OA\Response(
                response: 404,
                description: 'Cuenta inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos inválidos o saldo insuficiente (decrease)',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function adjustBalance(string $id, AdjustAccountBalanceRequest $request): AccountResource
    {
        $account = $this->adjustBalance->adjust($request->toCommand($id));

        return new AccountResource($account);
    }

    /**
     * Consulta el extracto (movimientos) de una cuenta.
     *
     * Devuelve los débitos y créditos del libro mayor del más reciente al
     * más antiguo, con el saldo resultante de cada movimiento.
     */
    #[OA\Get(
        path: '/v1/accounts/{id}/movements',
        summary: 'Extracto de una cuenta',
        tags: ['Accounts'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'UUID de la cuenta', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', maximum: 100, default: 20)),
            new OA\Parameter(name: 'offset', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 0)),
            new OA\Parameter(name: 'X-Correlation-Id', in: 'header', required: false, description: 'Identificador de correlación para trazabilidad', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Página de movimientos',
                content: new OA\JsonContent(ref: '#/components/schemas/AccountMovementPage'),
            ),
            new OA\Response(
                response: 404,
                description: 'Cuenta inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function movements(string $id, ListAccountMovementsRequest $request): AccountMovementCollectionResource
    {
        $page = $this->listMovements->execute($request->toCriteria($id));

        return new AccountMovementCollectionResource($page->items, $page->total, $page->limit, $page->offset);
    }
}
