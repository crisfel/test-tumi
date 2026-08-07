<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use PayIn\Infrastructure\Persistence\Eloquent\Models\PaymentProviderModel;

/**
 * Siembra el catálogo de proveedores de pago con su matriz de capacidades
 * (tipos de método que cada pasarela puede procesar).
 *
 * Los IDs son FIJOS para que el usuario pueda copiar los ejemplos de Swagger
 * tal cual y que "Try it out" funcione sin modificaciones. Agregar un
 * proveedor nuevo: crear su adapter, registrarlo aquí (o en una migración de
 * datos) y declarar sus supported_types. Ningún código de dominio cambia.
 */
final class PaymentProviderSeeder extends Seeder
{
    public const ID_FAKEPAY = '019fd715-eb24-7683-b8d2-9d83ffdca22d';

    public const ID_SANDBOXPAY = '019fd715-eb5a-7000-8000-000000000002';

    public const ID_CASH = '019fd715-eb6a-7000-8000-000000000003';

    public function run(): void
    {
        $providers = [
            [
                'id' => self::ID_FAKEPAY,
                'code' => 'fakepay',
                'name' => 'FakePay',
                'is_active' => true,
                'supported_types' => ['card'],
            ],
            [
                'id' => self::ID_SANDBOXPAY,
                'code' => 'sandboxpay',
                'name' => 'SandboxPay',
                'is_active' => true,
                'supported_types' => ['card', 'bank_transfer', 'wallet', 'pse'],
            ],
            [
                'id' => self::ID_CASH,
                'code' => 'cash',
                'name' => 'Efectivo',
                'is_active' => true,
                'supported_types' => ['cash'],
            ],
        ];

        foreach ($providers as $provider) {
            PaymentProviderModel::query()->updateOrCreate(
                ['code' => $provider['code']],
                [
                    'id' => $provider['id'],
                    'name' => $provider['name'],
                    'is_active' => $provider['is_active'],
                    'supported_types' => $provider['supported_types'],
                    'configuration' => [],
                ],
            );
        }
    }
}
