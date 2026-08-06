<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PayIn\Infrastructure\Persistence\Eloquent\Factories\AccountFactory;

/**
 * Modelo de persistencia del aggregate Account.
 *
 * Solo capa Infrastructure: nunca contiene lógica de negocio.
 */
final class AccountModel extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    protected $table = 'accounts';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'client_id',
        'currency',
        'balance',
    ];

    protected $casts = [
        'balance' => 'integer',
    ];

    /**
     * @return BelongsTo<ClientModel, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(ClientModel::class, 'client_id');
    }

    /**
     * @return HasMany<PaymentMethodModel, $this>
     */
    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethodModel::class, 'account_id');
    }

    protected function newFactory(): AccountFactory
    {
        return new AccountFactory();
    }
}
