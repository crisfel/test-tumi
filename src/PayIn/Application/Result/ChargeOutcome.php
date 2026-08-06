<?php

declare(strict_types=1);

namespace PayIn\Application\Result;

/**
 * Desenlace de la operación de cobro según la respuesta del proveedor.
 *
 * El adapter normaliza cualquier respuesta (exitosa, rechazo, timeout o
 * error) a uno de estos cuatro resultados; nunca arroja excepciones de
 * control de flujo por respuestas de negocio.
 */
enum ChargeOutcome: string
{
    case SUCCESS = 'success';

    case REJECTED = 'rejected';

    case TIMEOUT = 'timeout';

    case ERROR = 'error';
}
