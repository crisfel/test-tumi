<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use PayIn\Domain\PaymentProvider\ProviderId;
use PayIn\Infrastructure\Persistence\Eloquent\Models\PaymentProviderModel;

/**
 * Siembra el catálogo de proveedores de pago disponibles en la plataforma.
 *
 * Agregar un proveedor nuevo: crear su adapter y añadir su registro aquí
 * (o en una migración de datos). Ningún código de dominio cambia.
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
            ],
            [
                'code' => 'sandboxpay',
                'name' => 'SandboxPay',
                'is_active' => true,
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
                'configuration' => [],
            ]);
        }
    }
}
