<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use PayIn\Domain\PaymentProvider\ProviderId;
use PayIn\Infrastructure\Persistence\Eloquent\Models\PaymentProviderModel;

/**
 * Siembra el catálogo de proveedores de pago con su matriz de capacidades
 * (tipos de método que cada pasarela puede procesar).
 *
 * Agregar un proveedor nuevo: crear su adapter, registrarlo aquí (o en una
 * migración de datos) y declarar sus supported_types. Ningún código de
 * dominio cambia.
 */
final class PaymentProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                'code' => 'fakepay',
                'name' => 'FakePay',
                'is_active' => true,
                'supported_types' => ['card'],
            ],
            [
                'code' => 'sandboxpay',
                'name' => 'SandboxPay',
                'is_active' => true,
                'supported_types' => ['card', 'bank_transfer', 'wallet', 'pse'],
            ],
            [
                'code' => 'cash',
                'name' => 'Efectivo',
                'is_active' => true,
                'supported_types' => ['cash'],
            ],
        ];

        foreach ($providers as $provider) {
            $existing = PaymentProviderModel::query()->where('code', $provider['code'])->first();

            if ($existing === null) {
                PaymentProviderModel::query()->create([
                    'id' => ProviderId::generate()->toString(),
                    ...$provider,
                    'configuration' => [],
                ]);

                continue;
            }

            $existing->update([
                'name' => $provider['name'],
                'is_active' => $provider['is_active'],
                'supported_types' => $provider['supported_types'],
                'configuration' => [],
            ]);
        }
    }
}
