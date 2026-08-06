<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de persistencia de los datos específicos de un PayIn.
 *
 * Solo capa Infrastructure: nunca contiene lógica de negocio.
 */
final class PayInModel extends Model
{
    protected $table = 'pay_ins';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'account_id',
        'payment_method_id',
        'fees',
    ];

    protected $casts = [
        'fees' => 'integer',
    ];

    /**
     * @return BelongsTo<TransactionModel, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(TransactionModel::class, 'transaction_id');
    }

    /**
     * @return BelongsTo<AccountModel, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }

    /**
     * @return BelongsTo<PaymentMethodModel, $this>
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethodModel::class, 'payment_method_id');
    }
}
