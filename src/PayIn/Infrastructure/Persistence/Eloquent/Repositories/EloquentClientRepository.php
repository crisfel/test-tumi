<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Repositories;

use PayIn\Domain\Client\Client;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Contracts\ClientRepository;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\ClientMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Models\ClientModel;

/**
 * Implementación Eloquent del puerto ClientRepository.
 */
final readonly class EloquentClientRepository implements ClientRepository
{
    public function __construct(private ClientMapper $mapper)
    {
    }

    public function findById(ClientId $id): ?Client
    {
        $model = ClientModel::query()->find($id->toString());

        return $model !== null ? $this->mapper->fromModel($model) : null;
    }
}
