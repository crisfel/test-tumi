<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use PayIn\Application\UseCase\ListPayInsService;
use PayIn\Application\UseCase\ProcessPayInService;
use PayIn\Application\UseCase\QueryPayInService;
use PayIn\Domain\PayIn\TransactionId;
use PayIn\Infrastructure\Http\FormRequests\ListPayInsRequest;
use PayIn\Infrastructure\Http\FormRequests\StorePayInRequest;
use PayIn\Infrastructure\Http\Resources\PayInCollectionResource;
use PayIn\Infrastructure\Http\Resources\PayInResource;

/**
 * Endpoints públicos del componente PayIn (API v1).
 *
 * Controlador "delgado": valida la entrada, delega en los casos de uso y
 * serializa la respuesta. No contiene lógica de negocio.
 */
#[OA\Info(
    version: '1.0.0',
    description: <<<'DESC'
API del componente **PayIn**: procesamiento de ingresos de fondos (transferencias entre cuentas de la plataforma).

## 🧪 Prueba en 5 pasos (Ana → Pedro)

Los datos de la demo ya están sembrados y los ejemplos de esta documentación usan los mismos IDs, así que **"Try it out" funciona sin editar nada**.

1. **POST /v1/payins** → "Try it out" → pega este JSON → "Execute":
```json
{
  "client_id": "019fd715-ebf8-7223-ada8-b3c168a28e22",
  "origin_account_id": "019fd715-ec1a-7a7e-ab6f-f497aa52abe4",
  "account_id": "019fd715-ec22-700c-8cba-ea026d0fd9a9",
  "payment_method_id": "019fd715-ec43-784b-97dd-9b2fe70bfe69",
  "amount": 25000,
  "currency": "COP",
  "reference": "order-2026-0001"
}
```
2. Respuesta **201** con `"status": "processed"` y un `"id"`.
3. **¿Bajó el de Ana?** `GET /v1/accounts/019fd715-ec1a-7a7e-ab6f-f497aa52abe4` → `"balance": 75000`.
4. **¿Subió el de Pedro?** `GET /v1/accounts/019fd715-ec22-700c-8cba-ea026d0fd9a9` → `"balance": 25000`.
5. **Historial y estados:** `GET /v1/payins?client_id={id_ana}` y `GET /v1/payins?status=processed|failed`.

**Estados del PayIn:** `CREATED → VALIDATED → PROCESSING → PROCESSED/FAILED` (síncrono: solo queda el estado final). Para ver un `FAILED`, configura `PAYIN_FAKEPAY_BEHAVIOR=rejected` en `.env`, reinicia el contenedor y crea otro PayIn con la tarjeta.

> Detalle: los IDs de los clientes/cuentas/métodos están fijos y aparecen en la guía visual de esta página.
DESC,
    title: 'PayIn Platform',
)]
#[OA\Server(url: 'http://localhost:8080/api', description: 'Servidor de desarrollo')]
#[OA\Tag(name: 'PayIns', description: 'Operaciones PayIn')]
#[OA\Tag(name: 'Clients', description: 'Operaciones de clientes')]
#[OA\Tag(name: 'Accounts', description: 'Operaciones de cuentas')]
#[OA\Tag(name: 'Payment Methods', description: 'Operaciones de métodos de pago')]
#[OA\Tag(name: 'Payment Providers', description: 'Catálogo de proveedores de pago')]
#[OA\Tag(name: 'System', description: 'Operaciones de sistema')]
final readonly class PayInController
{
    public function __construct(
        private ProcessPayInService $processPayIn,
        private QueryPayInService $queryPayIn,
        private ListPayInsService $listPayIns,
    ) {
    }

    /**
     * Crea y procesa un PayIn.
     *
     * La operación valida los aggregates, persiste el PayIn (CREATED →
     * VALIDATED), delega el cobro al proveedor resuelto por el método de
     * pago y aplica el resultado (PROCESSED/FAILED). Si el proveedor
     * rechaza la operación, el PayIn se crea con estado FAILED y el
     * error_code correspondiente.
     */
    #[OA\Post(
        path: '/v1/payins',
        summary: 'Procesar un PayIn',
        tags: ['PayIns'],
        parameters: [
            new OA\Parameter(name: 'X-Correlation-Id', in: 'header', required: false, description: 'Identificador de correlación para trazabilidad', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StorePayInRequest'),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'PayIn creado y procesado (estado processed o failed según el proveedor)',
                content: new OA\JsonContent(ref: '#/components/schemas/PayInResponse'),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos de entrada inválidos o invariantes de dominio violadas',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 404,
                description: 'Cliente, cuenta, método de pago o proveedor inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 409,
                description: 'Referencia ya utilizada o conflicto de estado/concurrencia',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 502,
                description: 'Error inesperado del proveedor de pago',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function store(StorePayInRequest $request): JsonResponse
    {
        $response = $this->processPayIn->process($request->toCommand());

        $payIn = $this->queryPayIn->findByIdOrFail($response->payInId);

        return (new PayInResource($payIn))->response()->setStatusCode(201);
    }

    /**
     * Consulta un PayIn por su identificador.
     */
    #[OA\Get(
        path: '/v1/payins/{id}',
        summary: 'Consultar un PayIn',
        tags: ['PayIns'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'UUID del PayIn', schema: new OA\Schema(type: 'string', format: 'uuid', example: '019fd738-d4f2-7bc9-916b-0ae687b42038')),
            new OA\Parameter(name: 'X-Correlation-Id', in: 'header', required: false, description: 'Identificador de correlación para trazabilidad', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'PayIn encontrado',
                content: new OA\JsonContent(ref: '#/components/schemas/PayInResponse'),
            ),
            new OA\Response(
                response: 404,
                description: 'PayIn inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function show(Request $request, string $id): PayInResource
    {
        $payIn = $this->queryPayIn->findByIdOrFail(TransactionId::fromString($id));

        return new PayInResource($payIn);
    }

    /**
     * Lista PayIns con filtros y paginación.
     */
    #[OA\Get(
        path: '/v1/payins',
        summary: 'Listar PayIns',
        tags: ['PayIns'],
        parameters: [
            new OA\Parameter(name: 'client_id', in: 'query', required: false, description: 'UUID del cliente: devuelve su HISTORIAL de transacciones (ej.: Ana)', schema: new OA\Schema(type: 'string', format: 'uuid', example: '019fd715-ebf8-7223-ada8-b3c168a28e22')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['created', 'validated', 'processing', 'processed', 'failed'])),
            new OA\Parameter(name: 'from', in: 'query', required: false, description: 'Fecha inicial (ISO 8601)', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'to', in: 'query', required: false, description: 'Fecha final (ISO 8601)', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', maximum: 100, default: 20)),
            new OA\Parameter(name: 'offset', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 0)),
            new OA\Parameter(name: 'X-Correlation-Id', in: 'header', required: false, description: 'Identificador de correlación para trazabilidad', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Página de PayIns',
                content: new OA\JsonContent(ref: '#/components/schemas/PayInPage'),
            ),
        ],
    )]
    public function index(ListPayInsRequest $request): PayInCollectionResource
    {
        $page = $this->listPayIns->execute($request->toCriteria());

        return new PayInCollectionResource($page->items, $page->total, $page->limit, $page->offset);
    }
}
