<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Services;

use Closure;
use Illuminate\Support\Facades\DB;
use PayIn\Application\Port\TransactionManager;

/**
 * Implementación del puerto TransactionManager basada en transacciones de
 * base de datos de Laravel (Unit of Work).
 */
final class LaravelTransactionManager implements TransactionManager
{
    public function execute(callable $callback): mixed
    {
        return DB::transaction(Closure::fromCallable($callback));
    }
}
