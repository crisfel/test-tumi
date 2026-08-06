<?php

declare(strict_types=1);

namespace PayIn\Application\Port;

/**
 * Puerto de logging estructurado.
 *
 * El dominio no loguea; la aplicación utiliza este puerto para registrar
 * pasos relevantes del orquestador con contexto enriquecido.
 */
interface Logger
{
    /**
     * @param array<string, mixed> $context
     */
    public function info(string $message, array $context = []): void;

    /**
     * @param array<string, mixed> $context
     */
    public function error(string $message, array $context = []): void;

    /**
     * @param array<string, mixed> $context
     */
    public function warning(string $message, array $context = []): void;
}
