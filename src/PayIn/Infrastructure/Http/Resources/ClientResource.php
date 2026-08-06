<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PayIn\Domain\Client\Client;

/**
 * Representación pública de un cliente.
 *
 * Nunca expone modelos internos; sólo los datos públicos del registro.
 */
final class ClientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Client $client */
        $client = $this->resource;

        return [
            'id' => $client->id()->toString(),
            'name' => $client->name(),
            'email' => $client->email()->value(),
        ];
    }
}
