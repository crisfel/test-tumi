<?php

declare(strict_types=1);

namespace Tests\Feature\PayIn;

use PayIn\Infrastructure\Persistence\Eloquent\Mappers\AccountMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\ClientMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Tests\Support\PayInFixtures;

final class ProcessPayInApiTest extends PayInApiTestCase
{
    public function test_creates_and_processes_a_payin_successfully(): void
    {
        $response = $this->postJson('/api/v1/payins', $this->validPayload([
            'reference' => 'order-2026-0001',
        ]));

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id', 'client_id', 'account_id', 'payment_method_id',
                    'amount', 'currency', 'status', 'reference',
                    'provider_transaction_id', 'error_code', 'created_at',
                ],
            ])
            ->assertJsonPath('data.status', 'processed')
            ->assertJsonPath('data.amount', 25000)
            ->assertJsonPath('data.currency', 'COP')
            ->assertJsonPath('data.reference', 'order-2026-0001')
            ->assertJsonPath('data.error_code', null);

        $this->assertNotNull($response->json('data.id'));
        $this->assertStringStartsWith('FP-', (string) $response->json('data.provider_transaction_id'));

        $this->assertDatabaseHas('transactions', [
            'id' => $response->json('data.id'),
            'status' => 'processed',
        ]);
    }

    public function test_credits_account_balance_on_success(): void
    {
        $this->postJson('/api/v1/payins', $this->validPayload(['amount' => 15000]));

        $this->assertSame(15000, (int) AccountModel::query()->find($this->account->id()->toString())->balance);
    }

    public function test_does_not_credit_account_when_provider_rejects(): void
    {
        config()->set('payin.providers.fakepay.behavior', 'rejected');

        $response = $this->postJson('/api/v1/payins', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.error_code', 'PROVIDER_REJECTED');

        $this->assertSame(0, (int) AccountModel::query()->find($this->account->id()->toString())->balance);
    }

    public function test_marks_failed_on_provider_timeout(): void
    {
        config()->set('payin.providers.fakepay.behavior', 'timeout');

        $response = $this->postJson('/api/v1/payins', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.error_code', 'timeout');
    }

    public function test_marks_failed_on_provider_error(): void
    {
        config()->set('payin.providers.fakepay.behavior', 'error');

        $response = $this->postJson('/api/v1/payins', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.error_code', 'PROVIDER_ERROR');
    }

    public function test_rejects_invalid_uuid(): void
    {
        $response = $this->postJson('/api/v1/payins', $this->validPayload([
            'client_id' => 'not-a-uuid',
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['errors' => [['code', 'message', 'meta']]]);
    }

    public function test_rejects_unknown_currency(): void
    {
        $response = $this->postJson('/api/v1/payins', $this->validPayload([
            'currency' => 'BTC',
        ]));

        $response->assertStatus(422);
    }

    public function test_rejects_non_positive_amount(): void
    {
        $response = $this->postJson('/api/v1/payins', $this->validPayload([
            'amount' => 0,
        ]));

        $response->assertStatus(422);
    }

    public function test_rejects_decimal_amount(): void
    {
        $response = $this->postJson('/api/v1/payins', $this->validPayload([
            'amount' => 250.5,
        ]));

        $response->assertStatus(422);
    }

    public function test_rejects_invalid_reference(): void
    {
        $response = $this->postJson('/api/v1/payins', $this->validPayload([
            'reference' => 'bad reference!',
        ]));

        $response->assertStatus(422);
    }

    public function test_rejects_missing_required_fields(): void
    {
        $response = $this->postJson('/api/v1/payins', [
            'amount' => 1000,
        ]);

        $response->assertStatus(422);
    }

    public function test_returns_404_when_client_does_not_exist(): void
    {
        $response = $this->postJson('/api/v1/payins', $this->validPayload([
            'client_id' => \PayIn\Domain\Client\ClientId::generate()->toString(),
        ]));

        $response->assertStatus(404)
            ->assertJsonPath('errors.0.code', 'CLIENT_NOT_FOUND');
    }

    public function test_returns_404_when_account_does_not_exist(): void
    {
        $response = $this->postJson('/api/v1/payins', $this->validPayload([
            'account_id' => \PayIn\Domain\Account\AccountId::generate()->toString(),
        ]));

        $response->assertStatus(404)
            ->assertJsonPath('errors.0.code', 'ACCOUNT_NOT_FOUND');
    }

    public function test_returns_404_when_payment_method_does_not_exist(): void
    {
        $response = $this->postJson('/api/v1/payins', $this->validPayload([
            'payment_method_id' => \PayIn\Domain\PaymentMethod\PaymentMethodId::generate()->toString(),
        ]));

        $response->assertStatus(404)
            ->assertJsonPath('errors.0.code', 'PAYMENT_METHOD_NOT_FOUND');
    }

    public function test_returns_409_when_reference_is_already_used(): void
    {
        $this->postJson('/api/v1/payins', $this->validPayload(['reference' => 'order-dup-0001']));

        $response = $this->postJson('/api/v1/payins', $this->validPayload(['reference' => 'order-dup-0001']));

        $response->assertStatus(409)
            ->assertJsonPath('errors.0.code', 'REFERENCE_ALREADY_USED');
    }

    public function test_returns_422_when_method_type_is_not_supported_by_provider(): void
    {
        // Defensa del dominio ante datos inconsistentes: un método PSE sobre
        // un proveedor que sólo soporta card (el registro lo bloquea, pero
        // la validación del PayIn también lo protege).
        $inconsistent = \PayIn\Domain\PaymentMethod\PaymentMethod::register(
            \PayIn\Domain\PaymentMethod\PaymentMethodId::generate(),
            $this->provider->id(),
            \PayIn\Domain\PaymentMethod\PaymentMethodType::PSE,
            'tok_pse_x_0001',
            'Banco X',
            new \DateTimeImmutable('2026-01-01'),
        );
        (new \PayIn\Infrastructure\Persistence\Eloquent\Mappers\PaymentMethodMapper())->toModel($inconsistent)->save();

        $response = $this->postJson('/api/v1/payins', $this->validPayload([
            'payment_method_id' => $inconsistent->id()->toString(),
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('errors.0.code', 'PAYMENT_METHOD_TYPE_NOT_SUPPORTED');
    }

    public function test_credits_destination_account_of_another_client(): void
    {
        // Escenario Ana → Pedro: Ana paga con su método y el saldo de la
        // cuenta de Pedro aumenta (la cuenta destino no tiene pertenencia).
        $pedro = PayInFixtures::client(email: 'pedro.perez@example.com');
        (new ClientMapper())->toModel($pedro)->save();
        $pedroAccount = PayInFixtures::account($pedro->id());
        (new AccountMapper())->toModel($pedroAccount)->save();

        $response = $this->postJson('/api/v1/payins', $this->validPayload([
            'account_id' => $pedroAccount->id()->toString(),
            'amount' => 50000,
            'reference' => 'ana-paga-a-pedro-0001',
        ]));

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'processed')
            ->assertJsonPath('data.account_id', $pedroAccount->id()->toString());

        $this->assertSame(50000, (int) AccountModel::query()->find($pedroAccount->id()->toString())->balance);
        $this->assertSame(0, (int) AccountModel::query()->find($this->account->id()->toString())->balance);
    }

    public function test_processes_cash_payin_immediately(): void
    {
        $response = $this->postJson('/api/v1/payins', $this->validPayload([
            'payment_method_id' => $this->cashMethod->id()->toString(),
            'amount' => 8000,
            'reference' => 'pago-efectivo-0001',
        ]));

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'processed')
            ->assertJsonPath('data.error_code', null);

        $this->assertStringStartsWith('CASH-', (string) $response->json('data.provider_transaction_id'));
    }

    public function test_returns_422_when_currency_does_not_match_account(): void
    {
        $response = $this->postJson('/api/v1/payins', $this->validPayload([
            'currency' => 'USD',
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('errors.0.code', 'CURRENCY_MISMATCH');
    }

    public function test_processes_payin_with_sandbox_provider(): void
    {
        config()->set('payin.providers.sandboxpay.behavior', 'success');

        $response = $this->postJson('/api/v1/payins', $this->validPayload([
            'account_id' => $this->usdAccount->id()->toString(),
            'payment_method_id' => $this->sandboxMethod->id()->toString(),
            'currency' => 'USD',
            'amount' => 9900,
        ]));

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'processed')
            ->assertJsonPath('data.currency', 'USD');
        $this->assertStringStartsWith('SP-', (string) $response->json('data.provider_transaction_id'));
    }

    public function test_error_envelope_is_consistent_across_endpoints(): void
    {
        $response = $this->postJson('/api/v1/payins', []);

        $response->assertStatus(422);
        $this->assertArrayHasKey('errors', $response->json());
        $this->assertArrayHasKey('code', $response->json('errors.0'));
        $this->assertArrayHasKey('message', $response->json('errors.0'));
        $this->assertArrayNotHasKey('exception', $response->json());
        $this->assertArrayNotHasKey('trace', $response->json());
    }
}
