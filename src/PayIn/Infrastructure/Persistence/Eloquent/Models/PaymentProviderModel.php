<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PayIn\Infrastructure\Persistence\Eloquent\Factories\PaymentProviderFactory;

/**
 * Modelo de persistencia del aggregate PaymentProvider.
 *
 * Solo capa Infrastructure: nunca contiene lógica de negocio.
 */
final class PaymentProviderModel extends Model
{
    /** @use HasFactory<PaymentProviderFactory> */
    use HasFactory;

    protected $table = 'payment_providers';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'code',
        'name',
        'is_active',
        'supported_types',
        'configuration',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'supported_types' => 'array',
        'configuration' => 'array',
    ];

    protected function newFactory(): PaymentProviderFactory
    {
        return new PaymentProviderFactory();
    }
}
