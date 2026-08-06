<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PayIn\Infrastructure\Persistence\Eloquent\Factories\PaymentMethodFactory;

/**
 * Modelo de persistencia del aggregate PaymentMethod.
 *
 * Solo capa Infrastructure: nunca contiene lógica de negocio.
 */
final class PaymentMethodModel extends Model
{
    /** @use HasFactory<PaymentMethodFactory> */
    use HasFactory;

    protected $table = 'payment_methods';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'account_id',
        'provider_id',
        'type',
        'token',
        'details_masked',
        'is_active',
        'created_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<AccountModel, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }

    /**
     * @return BelongsTo<PaymentProviderModel, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(PaymentProviderModel::class, 'provider_id');
    }

    protected function newFactory(): PaymentMethodFactory
    {
        return new PaymentMethodFactory();
    }
}
