<?php

declare(strict_types=1);

namespace Tests\Feature\PayIn;

use PayIn\Domain\PaymentProvider\ProviderId;

final class PaymentProviderApiTest extends PayInApiTestCase
{
    public function test_returns_provider_by_id_with_capabilities(): void
    {
        $response = $this->getJson('/api/v1/payment-providers/' . $this->provider->id()->toString());

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->provider->id()->toString())
            ->assertJsonPath('data.code', 'fakepay')
            ->assertJsonPath('data.name', 'FakePay')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.supported_types', ['card']);
    }

    public function test_returns_404_when_provider_does_not_exist(): void
    {
        $response = $this->getJson('/api/v1/payment-providers/' . ProviderId::generate()->toString());

        $response->assertStatus(404)
            ->assertJsonPath('errors.0.code', 'PROVIDER_NOT_FOUND');
    }

    public function test_lists_providers_with_capabilities(): void
    {
        $response = $this->getJson('/api/v1/payment-providers');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 3);

        $codes = collect($response->json('data'))->pluck('code')->all();
        $this->assertContains('fakepay', $codes);
        $this->assertContains('sandboxpay', $codes);
        $this->assertContains('cash', $codes);
    }

    public function test_sandbox_provider_declares_multiple_capabilities(): void
    {
        $response = $this->getJson('/api/v1/payment-providers/' . $this->sandboxProvider->id()->toString());

        $this->assertContains('pse', $response->json('data.supported_types'));
        $this->assertContains('wallet', $response->json('data.supported_types'));
    }
}
