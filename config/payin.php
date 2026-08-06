<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Proveedores de pago
    |--------------------------------------------------------------------------
    |
    | Cada proveedor registrado define su comportamiento simulado (útil en
    | desarrollo y pruebas) y la latencia artificial en milisegundos.
    |
    | behaviors: success | rejected | timeout | error
    |
    */

    'providers' => [
        'fakepay' => [
            'behavior' => env('PAYIN_FAKEPAY_BEHAVIOR', 'success'),
            'latency_ms' => (int) env('PAYIN_FAKEPAY_LATENCY_MS', 0),
        ],
        'sandboxpay' => [
            'behavior' => env('PAYIN_SANDBOXPAY_BEHAVIOR', 'success'),
            'latency_ms' => (int) env('PAYIN_SANDBOXPAY_LATENCY_MS', 0),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Registro de adapters
    |--------------------------------------------------------------------------
    |
    | Mapa código-de-proveedor => clase adapter. Agregar un proveedor nuevo:
    |   1. Crear una clase que implemente PaymentGateway.
    |   2. Añadir una entrada aquí (o inyectarla desde el contenedor).
    |   3. Registrar el proveedor en el catálogo (seeder/migración).
    |
    | Ninguna clase del dominio o de la aplicación se modifica (OCP).
    |
    */

    'gateways' => [
        'fakepay' => \PayIn\Infrastructure\PaymentProviders\FakePayProvider::class,
        'sandboxpay' => \PayIn\Infrastructure\PaymentProviders\SandboxPayProvider::class,
    ],
];
