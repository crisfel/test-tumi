<?php

declare(strict_types=1);

namespace PayIn\Application\Port;

/**
 * Puerto de gestión transaccional (Unit of Work).
 *
 * Aísla la atomicidad de la persistencia: el orquestador describe la
 * operación y la infraestructura decide cómo ejecutarla (DB transaction,
 * saga, etc.).
 */
interface TransactionManager
{
    /**
     * Ejecuta un bloque de trabajo de forma atómica.
     *
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     *
     * @throws \Throwable propaga cualquier excepción y revierte la transacción
     */
    public function execute(callable $callback): mixed;
}
