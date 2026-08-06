<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Mappers;

use PayIn\Domain\Client\Client;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Email;
use PayIn\Infrastructure\Persistence\Eloquent\Models\ClientModel;

/**
 * Traduce entre el aggregate Client y su representación persistente.
 */
final class ClientMapper
{
    public function toModel(Client $client): ClientModel
    {
        $model = new ClientModel();
        $model->id = $client->id()->toString();
        $model->name = $client->name();
        $model->email = $client->email()->value();

        return $model;
    }

    public function fromModel(ClientModel $model): Client
    {
        return Client::reconstitute(
            ClientId::fromString($model->id),
            $model->name,
            Email::fromString($model->email),
        );
    }
}
