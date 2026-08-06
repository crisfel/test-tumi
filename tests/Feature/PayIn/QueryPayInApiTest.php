<?php

declare(strict_types=1);

namespace Tests\Feature\PayIn;

use PayIn\Domain\PayIn\PayIn;
use PayIn\Domain\PayIn\PayInStatus;
use PayIn\Domain\PayIn\Reference;
use PayIn\Domain\PayIn\TransactionId;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\PayInMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Repositories\EloquentPayInRepository;

final class QueryPayInApiTest extends PayInApiTestCase
{
    private EloquentPayInRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentPayInRepository(new PayInMapper());
    }

    private function persistPayIn(?Reference $reference = null, ?TransactionId $id = null): PayIn
    {
        $payIn = \Tests\Support\PayInFixtures::payIn(
            $this->client->id(),
            $this->account->id(),
            $this->method->id(),
            \PayIn\Domain\Money::fromMinorUnits(25000, \PayIn\Domain\Currency::COP),
            $reference,
            $id,
        );
        $this->repository->save($payIn);

        return $payIn;
    }

    public function test_returns_payin_by_id(): void
    {
        $payIn = $this->persistPayIn(Reference::fromString('order-0001'));

        $response = $this->getJson('/api/v1/payins/' . $payIn->id()->toString());

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $payIn->id()->toString())
            ->assertJsonPath('data.status', 'created')
            ->assertJsonPath('data.amount', 25000)
            ->assertJsonPath('data.currency', 'COP')
            ->assertJsonPath('data.reference', 'order-0001');
    }

    public function test_returns_404_when_payin_does_not_exist(): void
    {
        $response = $this->getJson('/api/v1/payins/' . TransactionId::generate()->toString());

        $response->assertStatus(404)
            ->assertJsonPath('errors.0.code', 'PAYIN_NOT_FOUND');
    }

    public function test_returns_404_for_invalid_uuid_in_path(): void
    {
        $response = $this->getJson('/api/v1/payins/not-a-uuid');

        $response->assertStatus(404);
    }

    public function test_lists_payins_with_pagination(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->persistPayIn();
        }

        $response = $this->getJson('/api/v1/payins?limit=2&offset=0');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.limit', 2)
            ->assertJsonPath('meta.offset', 0);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_lists_payins_filtered_by_status(): void
    {
        for ($i = 1; $i <= 2; $i++) {
            $this->persistPayIn();
        }

        $payIn = $this->persistPayIn();
        $payIn->markFailed('X', 'y');
        $this->repository->save($payIn);

        $response = $this->getJson('/api/v1/payins?status=failed');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame(PayInStatus::FAILED->value, $response->json('data.0.status'));
    }

    public function test_lists_payins_rejects_unknown_status(): void
    {
        $response = $this->getJson('/api/v1/payins?status=exploded');

        $response->assertStatus(422);
    }

    public function test_lists_payins_rejects_limit_above_max(): void
    {
        $response = $this->getJson('/api/v1/payins?limit=1000');

        $response->assertStatus(422);
    }
}
