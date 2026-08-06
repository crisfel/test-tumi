<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use PayIn\Infrastructure\Persistence\Eloquent\Factories\TransactionFactory;

/**
 * Modelo de persistencia del núcleo financiero Transaction.
 *
 * Solo capa Infrastructure: nunca contiene lógica de negocio.
 */
final class TransactionModel extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    protected $table = 'transactions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'type',
        'client_id',
        'amount',
        'currency',
        'status',
        'reference',
        'provider_id',
        'provider_transaction_id',
        'provider_response',
        'error_code',
        'error_message',
        'created_at',
        'processed_at',
        'updated_at',
        'version',
    ];

    protected $casts = [
        'amount' => 'integer',
        'provider_response' => 'array',
        'created_at' => 'datetime',
        'processed_at' => 'datetime',
        'updated_at' => 'datetime',
        'version' => 'integer',
    ];

    /**
     * @return HasOne<PayInModel, $this>
     */
    public function payIn(): HasOne
    {
        return $this->hasOne(PayInModel::class, 'transaction_id');
    }

    /**
     * @return BelongsTo<ClientModel, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(ClientModel::class, 'client_id');
    }

    /**
     * @return BelongsTo<PaymentProviderModel, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(PaymentProviderModel::class, 'provider_id');
    }

    protected function newFactory(): TransactionFactory
    {
        return new TransactionFactory();
    }
}
