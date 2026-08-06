<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de persistencia del libro mayor de saldos.
 *
 * Solo capa Infrastructure: nunca contiene lógica de negocio.
 */
final class AccountMovementModel extends Model
{
    protected $table = 'account_movements';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'account_id',
        'type',
        'amount',
        'currency',
        'balance_after',
        'pay_in_id',
        'occurred_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_after' => 'integer',
        'occurred_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<AccountModel, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }
}
