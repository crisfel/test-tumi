<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PayIn\Infrastructure\Persistence\Eloquent\Factories\ClientFactory;

/**
 * Modelo de persistencia del aggregate Client.
 *
 * Solo capa Infrastructure: nunca contiene lógica de negocio.
 */
final class ClientModel extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    protected $table = 'clients';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'email',
    ];

    protected function newFactory(): ClientFactory
    {
        return new ClientFactory();
    }
}
