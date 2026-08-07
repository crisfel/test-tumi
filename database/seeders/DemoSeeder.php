<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use PayIn\Domain\Account\AccountMovementId;
use PayIn\Domain\Currency;
use PayIn\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use PayIn\Infrastructure\Persistence\Eloquent\Models\AccountMovementModel;
use PayIn\Infrastructure\Persistence\Eloquent\Models\ClientModel;

/**
 * Datos de demostración: DOS clientes para que el usuario pueda probar la
 * transferencia Ana → Pedro de forma inmediata.
 *
 * - Ana García: paga / envía dinero (su cuenta COP es el ORIGEN, se debita).
 * - Pedro Pérez: recibe dinero (su cuenta COP es el DESTINO, se acredita).
 *
 * Los IDs son FIJOS y coinciden con los ejemplos de Swagger para que "Try
 * it out" funcione sin editar nada. El método de pago se siembra en
 * PaymentMethodSeeder.
 */
final class DemoSeeder extends Seeder
{
    public const ID_ANA = '019fd715-ebf8-7223-ada8-b3c168a28e22';

    public const ID_PEDRO = '019fd715-ed01-7000-8000-000000000001';

    public const ID_ANA_ACCOUNT_COP = '019fd715-ec1a-7a7e-ab6f-f497aa52abe4';

    public const ID_ANA_ACCOUNT_USD = '019fd715-ec2a-7000-8000-00000000000a';

    public const ID_PEDRO_ACCOUNT_COP = '019fd715-ec22-700c-8cba-ea026d0fd9a9';

    public function run(): void
    {
        $this->seedClientAndAccounts();
        $this->seedLedgerOpeningBalance();
    }

    private function seedClientAndAccounts(): void
    {
        $ana = $this->client(self::ID_ANA, 'Ana García', 'ana.garcia@example.com');
        $pedro = $this->client(self::ID_PEDRO, 'Pedro Pérez', 'pedro.perez@example.com');

        $this->account(self::ID_ANA_ACCOUNT_COP, $ana->id, Currency::COP, 100000);
        $this->account(self::ID_ANA_ACCOUNT_USD, $ana->id, Currency::USD, 0);
        $this->account(self::ID_PEDRO_ACCOUNT_COP, $pedro->id, Currency::COP, 0);
    }

    private function seedLedgerOpeningBalance(): void
    {
        AccountMovementModel::query()->firstOrCreate(
            ['account_id' => self::ID_ANA_ACCOUNT_COP, 'type' => 'credit', 'amount' => 100000, 'pay_in_id' => null],
            [
                'id' => AccountMovementId::generate()->toString(),
                'currency' => Currency::COP->value,
                'balance_after' => 100000,
                'occurred_at' => now(),
            ],
        );
    }

    private function client(string $id, string $name, string $email): ClientModel
    {
        return ClientModel::query()->updateOrCreate(
            ['id' => $id],
            ['name' => $name, 'email' => $email],
        );
    }

    private function account(string $id, string $clientId, Currency $currency, int $balance): AccountModel
    {
        return AccountModel::query()->updateOrCreate(
            ['id' => $id],
            ['client_id' => $clientId, 'currency' => $currency->value, 'balance' => $balance],
        );
    }
}
