<?php

declare(strict_types=1);

namespace PayIn\Domain\PaymentMethod;

/**
 * Tipos de método de pago soportados por la plataforma.
 *
 * Punto de extensión: agregar un nuevo tipo de método NO modifica el
 * dominio; sólo requiere su configuración de proveedor y adaptador.
 */
enum PaymentMethodType: string
{
    case CARD = 'card';

    case BANK_TRANSFER = 'bank_transfer';

    case WALLET = 'wallet';

    case PSE = 'pse';

    case CASH = 'cash';
}
