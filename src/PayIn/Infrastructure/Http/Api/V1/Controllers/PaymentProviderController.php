<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Api\V1\Controllers;

use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use PayIn\Application\UseCase\ListPaymentProvidersService;
use PayIn\Application\UseCase\QueryPaymentProviderService;
use PayIn\Domain\PaymentProvider\ProviderId;
use PayIn\Infrastructure\Http\Resources\PaymentProviderCollectionResource;
use PayIn\Infrastructure\Http\Resources\PaymentProviderResource;

/**
 * Endpoints del catálogo de proveedores de pago (API v1).
 *
 * Controlador "delgado": delega en los casos de uso y serializa.
 */
final readonly class PaymentProviderController
{
    public function __construct(
        private QueryPaymentProviderService $queryProvider,
        private ListPaymentProvidersService $listProviders,
    ) {
    }

    /**
     * Consulta un proveedor por su identificador (incluye sus capacidades).
     */
    #[OA\Get(
        path: '/v1/payment-providers/{id}',
        summary: 'Consultar un proveedor de pago',
        tags: ['Payment Providers'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'UUID del proveedor', schema: new OA\Schema(type: 'string', format: 'uuid', example: '019fd715-eb24-7683-b8d2-9d83ffdca22d')),
            new OA\Parameter(name: 'X-Correlation-Id', in: 'header', required: false, description: 'Identificador de correlación para trazabilidad', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Proveedor encontrado',
                content: new OA\JsonContent(ref: '#/components/schemas/PaymentProviderResponse'),
            ),
            new OA\Response(
                response: 404,
                description: 'Proveedor inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function show(Request $request, string $id): PaymentProviderResource
    {
        $provider = $this->queryProvider->findByIdOrFail(ProviderId::fromString($id));

        return new PaymentProviderResource($provider);
    }

    /**
     * Lista el catálogo de proveedores con sus capacidades.
     */
    #[OA\Get(
        path: '/v1/payment-providers',
        summary: 'Listar proveedores de pago',
        tags: ['Payment Providers'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', maximum: 100, default: 20)),
            new OA\Parameter(name: 'offset', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 0)),
            new OA\Parameter(name: 'X-Correlation-Id', in: 'header', required: false, description: 'Identificador de correlación para trazabilidad', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Página de proveedores',
                content: new OA\JsonContent(ref: '#/components/schemas/PaymentProviderPage'),
            ),
        ],
    )]
    public function index(Request $request): PaymentProviderCollectionResource
    {
        $limit = (int) ($request->query('limit', 20));
        $offset = (int) ($request->query('offset', 0));

        $page = $this->listProviders->execute($limit, $offset);

        return new PaymentProviderCollectionResource($page->items, $page->total, $page->limit, $page->offset);
    }
}
