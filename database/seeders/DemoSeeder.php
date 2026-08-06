<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Currency;
use PayIn\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use PayIn\Infrastructure\Persistence\Eloquent\Models\ClientModel;
use PayIn\Infrastructure\Persistence\Eloquent\Models\PaymentMethodModel;
use PayIn\Infrastructure\Persistence\Eloquent\Models\PaymentProviderModel;

/**
 * Datos de demostración: un cliente con cuentas y métodos de pago.
 */
final class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $client = ClientModel::query()->where('email', 'ana.garcia@example.com')->first();

        if ($client === null) {
            $client = ClientModel::query()->create([
                'id' => ClientId::generate()->toString(),
                'name' => 'Ana García',
                'email' => 'ana.garcia@example.com',
            ]);
        }

        $copAccount = AccountModel::query()->firstOrCreate(
            ['client_id' => $client->id, 'currency' => Currency::COP->value],
            ['id' => \PayIn\Domain\Account\AccountId::generate()->toString(), 'balance' => 0],
        );

        $usdAccount = AccountModel::query()->firstOrCreate(
            ['client_id' => $client->id, 'currency' => Currency::USD->value],
            ['id' => \PayIn\Domain\Account\AccountId::generate()->toString(), 'balance' => 0],
        );

        $fakepay = PaymentProviderModel::query()->where('code', 'fakepay')->firstOrFail();
        $sandboxpay = PaymentProviderModel::query()->where('code', 'sandboxpay')->firstOrFail();
        $cash = PaymentProviderModel::query()->where('code', 'cash')->firstOrFail();

        PaymentMethodModel::query()->firstOrCreate(
            ['provider_id' => $fakepay->id, 'token' => 'tok_card_visa_4242'],
            [
                'id' => \PayIn\Domain\PaymentMethod\PaymentMethodId::generate()->toString(),
                'type' => 'card',
                'details_masked' => '**** 4242',
                'is_active' => true,
                'created_at' => now(),
            ],
        );

        PaymentMethodModel::query()->firstOrCreate(
            ['provider_id' => $sandboxpay->id, 'token' => 'tok_pse_banco_001'],
            [
                'id' => \PayIn\Domain\PaymentMethod\PaymentMethodId::generate()->toString(),
                'type' => 'pse',
                'details_masked' => 'Banco Demo S.A.',
                'is_active' => true,
                'created_at' => now(),
            ],
        );

        PaymentMethodModel::query()->firstOrCreate(
            ['provider_id' => $sandboxpay->id, 'token' => 'tok_wallet_usr_999'],
            [
                'id' => \PayIn\Domain\PaymentMethod\PaymentMethodId::generate()->toString(),
                'type' => 'wallet',
                'details_masked' => 'wallet@ana.example',
                'is_active' => true,
                'created_at' => now(),
            ],
        );

        PaymentMethodModel::query()->firstOrCreate(
            ['provider_id' => $cash->id, 'token' => 'tok_cash_0001'],
            [
                'id' => \PayIn\Domain\PaymentMethod\PaymentMethodId::generate()->toString(),
                'type' => 'cash',
                'details_masked' => 'Efectivo',
                'is_active' => true,
                'created_at' => now(),
            ],
        );
    }
}
