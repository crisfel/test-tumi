<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use PayIn\Infrastructure\Persistence\Eloquent\Models\PaymentMethodModel;

/**
 * Siembra los métodos de pago de demostración, cada uno YA vinculado al id
 * de su proveedor (estándar de industria: el token pertenece a la pasarela
 * que lo emitió). Los IDs son fijos para que el usuario pueda copiarlos
 * directamente desde Swagger.
 *
 * Un método de pago es un instrumento independiente: no pertenece a un
 * cliente ni a una cuenta; cualquier cliente puede usarlo para procesar un
 * PayIn.
 */
final class PaymentMethodSeeder extends Seeder
{
    public const ID_CARD = '019fd715-ec43-784b-97dd-9b2fe70bfe69';

    public const ID_PSE = '019fd715-ec50-7000-8000-000000000001';

    public const ID_WALLET = '019fd715-ec51-7000-8000-000000000002';

    public const ID_CASH = '019fd715-ec52-7000-8000-000000000003';

    public function run(): void
    {
        $methods = [
            [
                'id' => self::ID_CARD,
                'provider_id' => PaymentProviderSeeder::ID_FAKEPAY,
                'type' => 'card',
                'token' => 'tok_card_visa_4242',
                'details_masked' => '**** 4242',
            ],
            [
                'id' => self::ID_PSE,
                'provider_id' => PaymentProviderSeeder::ID_SANDBOXPAY,
                'type' => 'pse',
                'token' => 'tok_pse_banco_001',
                'details_masked' => 'Banco Demo S.A.',
            ],
            [
                'id' => self::ID_WALLET,
                'provider_id' => PaymentProviderSeeder::ID_SANDBOXPAY,
                'type' => 'wallet',
                'token' => 'tok_wallet_usr_999',
                'details_masked' => 'wallet@demo.example',
            ],
            [
                'id' => self::ID_CASH,
                'provider_id' => PaymentProviderSeeder::ID_CASH,
                'type' => 'cash',
                'token' => 'tok_cash_0001',
                'details_masked' => 'Efectivo',
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethodModel::query()->updateOrCreate(
                ['id' => $method['id']],
                [
                    'provider_id' => $method['provider_id'],
                    'type' => $method['type'],
                    'token' => $method['token'],
                    'details_masked' => $method['details_masked'],
                    'is_active' => true,
                    'created_at' => now(),
                ],
            );
        }
    }
}
