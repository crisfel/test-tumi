<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\QueryException;
use PayIn\Application\Exception\ClientAlreadyExistsException;
use PayIn\Domain\Client\Client;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Contracts\ClientRepository;
use PayIn\Domain\Email;
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

    public function save(Client $client): void
    {
        $model = $this->mapper->toModel($client);
        $existing = ClientModel::query()->find($client->id()->toString());

        if ($existing === null) {
            try {
                $model->save();

                return;
            } catch (QueryException $exception) {
                if ($this->isEmailViolation($exception)) {
                    throw new ClientAlreadyExistsException($client->email()->value());
                }

                throw $exception;
            }
        }

        $existing->update([
            'name' => $model->name,
            'email' => $model->email,
        ]);
    }

    public function existsByEmail(Email $email): bool
    {
        return ClientModel::query()
            ->where('email', $email->value())
            ->exists();
    }

    private function isEmailViolation(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'email');
    }
}
