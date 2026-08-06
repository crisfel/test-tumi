<?php

declare(strict_types=1);

namespace Tests\Repositories\PayIn;

use PayIn\Application\Exception\PayInConcurrencyException;
use PayIn\Application\Exception\ReferenceAlreadyUsedException;
use PayIn\Domain\Account\Account;
use PayIn\Domain\Client\Client;
use PayIn\Domain\Contracts\PayInSearchCriteria;
use PayIn\Domain\Currency;
use PayIn\Domain\Money;
use PayIn\Domain\PayIn\PayIn;
use PayIn\Domain\PayIn\PayInStatus;
use PayIn\Domain\PayIn\ProviderResponse;
use PayIn\Domain\PayIn\ProviderTransactionId;
use PayIn\Domain\PayIn\Reference;
use PayIn\Domain\PayIn\TransactionId;
use PayIn\Domain\PaymentMethod\PaymentMethod;
use PayIn\Domain\PaymentProvider\PaymentProvider;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\AccountMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\ClientMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\PayInMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\PaymentMethodMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\PaymentProviderMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Models\TransactionModel;
use PayIn\Infrastructure\Persistence\Eloquent\Repositories\EloquentPayInRepository;
use Tests\Support\PayInFixtures;

final class EloquentPayInRepositoryTest extends RepositoryTestCase
{
    private EloquentPayInRepository $repository;

    private Client $client;

    private Account $account;

    private PaymentMethod $method;

    private PaymentProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentPayInRepository(new PayInMapper());

        $this->client = PayInFixtures::client();
        (new ClientMapper())->toModel($this->client)->save();

        $this->account = PayInFixtures::account($this->client->id());
        (new AccountMapper())->toModel($this->account)->save();

        $this->provider = PayInFixtures::provider();
        (new PaymentProviderMapper())->toModel($this->provider)->save();

        $this->method = PayInFixtures::method($this->provider->id());
        (new PaymentMethodMapper())->toModel($this->method)->save();
    }

    private function buildPayIn(?Reference $reference = null, ?TransactionId $id = null): PayIn
    {
        return PayInFixtures::payIn(
            $this->client->id(),
            $this->account->id(),
            $this->method->id(),
            Money::fromMinorUnits(25000, Currency::COP),
            $reference,
            $id,
        );
    }

    public function test_saves_and_reloads_a_new_payin(): void
    {
        $payIn = $this->buildPayIn(Reference::fromString('order-0001'));

        $this->repository->save($payIn);
        $reloaded = $this->repository->findById($payIn->id());

        $this->assertNotNull($reloaded);
        $this->assertTrue($payIn->id()->equals($reloaded->id()));
        $this->assertTrue($payIn->clientId()->equals($reloaded->clientId()));
        $this->assertTrue($payIn->accountId()->equals($reloaded->accountId()));
        $this->assertTrue($payIn->paymentMethodId()->equals($reloaded->paymentMethodId()));
        $this->assertSame(25000, $reloaded->amount()->minorUnits());
        $this->assertSame(Currency::COP, $reloaded->amount()->currency());
        $this->assertSame(PayInStatus::CREATED, $reloaded->status());
        $this->assertSame('order-0001', $reloaded->reference()->value());
        $this->assertSame($payIn->createdAt()->format('Y-m-d H:i:s'), $reloaded->createdAt()->format('Y-m-d H:i:s'));
    }

    public function test_updates_existing_payin_with_optimistic_locking(): void
    {
        $payIn = $this->buildPayIn();
        $this->repository->save($payIn);

        $payIn->markValidated();
        $this->repository->save($payIn);

        $reloaded = $this->repository->findById($payIn->id());

        $this->assertSame(PayInStatus::VALIDATED, $reloaded->status());
        $this->assertSame(2, TransactionModel::query()->find($payIn->id()->toString())->version);
    }

    public function test_round_trip_of_processed_payin(): void
    {
        $payIn = $this->buildPayIn();
        $this->repository->save($payIn);

        $providerId = $this->provider->id();
        $payIn->markValidated();
        $payIn->markProcessing();
        $payIn->markProcessed(
            $providerId,
            ProviderTransactionId::fromString('FP-20260101-0001'),
            ProviderResponse::fromArray(['auth' => 'ABC', 'avs' => 'Y']),
            new \DateTimeImmutable('2026-01-01 10:05:00 UTC'),
        );
        $this->repository->save($payIn);

        $reloaded = $this->repository->findById($payIn->id());

        $this->assertSame(PayInStatus::PROCESSED, $reloaded->status());
        $this->assertTrue($providerId->equals($reloaded->providerId()));
        $this->assertSame('FP-20260101-0001', $reloaded->providerTransactionId()->value());
        $this->assertSame('ABC', $reloaded->providerResponse()->toArray()['auth']);
        $this->assertNull($reloaded->errorCode());
        $this->assertNotNull($reloaded->processedAt());
    }

    public function test_throws_concurrency_exception_when_version_changed(): void
    {
        $payIn = $this->buildPayIn();
        $this->repository->save($payIn);

        TransactionModel::query()->whereKey($payIn->id()->toString())->update(['version' => 99]);

        $payIn->markValidated();

        $this->expectException(PayInConcurrencyException::class);

        $this->repository->save($payIn);
    }

    public function test_exists_by_reference(): void
    {
        $reference = Reference::fromString('order-0001');

        $this->assertFalse($this->repository->existsByReference($reference));

        $this->repository->save($this->buildPayIn($reference));

        $this->assertTrue($this->repository->existsByReference($reference));
    }

    public function test_rejects_duplicate_reference_on_insert(): void
    {
        $reference = Reference::fromString('order-0001');
        $this->repository->save($this->buildPayIn($reference));

        $this->expectException(ReferenceAlreadyUsedException::class);

        $this->repository->save($this->buildPayIn($reference));
    }

    public function test_matching_filters_by_status_and_returns_paginated_results(): void
    {
        $this->seedPayInsForMatching();

        $criteria = new PayInSearchCriteria(status: PayInStatus::PROCESSED, limit: 2, offset: 0);

        $page = $this->repository->matching($criteria);

        $this->assertCount(2, $page);
        foreach ($page as $payIn) {
            $this->assertSame(PayInStatus::PROCESSED, $payIn->status());
        }
        $this->assertSame(3, $this->repository->countMatching($criteria));
    }

    public function test_matching_filters_by_date_range(): void
    {
        $this->seedPayInsForMatching();

        $criteria = new PayInSearchCriteria(
            from: new \DateTimeImmutable('2026-01-01 09:00:00'),
            to: new \DateTimeImmutable('2026-01-01 12:00:00'),
        );

        $this->assertSame(3, $this->repository->countMatching($criteria));
    }

    public function test_matching_orders_by_created_at_desc(): void
    {
        $this->seedPayInsForMatching();

        $page = $this->repository->matching(new PayInSearchCriteria());

        $first = $page[0]->createdAt();
        $last = $page[count($page) - 1]->createdAt();

        $this->assertTrue($first >= $last, 'Los resultados deben ordenarse de más reciente a más antiguo.');
    }

    public function test_failed_payin_round_trip(): void
    {
        $payIn = $this->buildPayIn();
        $this->repository->save($payIn);
        $payIn->markFailed('PROVIDER_TIMEOUT', 'El proveedor no respondió.');
        $this->repository->save($payIn);

        $reloaded = $this->repository->findById($payIn->id());

        $this->assertSame(PayInStatus::FAILED, $reloaded->status());
        $this->assertSame('PROVIDER_TIMEOUT', $reloaded->errorCode());
        $this->assertSame('El proveedor no respondió.', $reloaded->errorMessage());
        $this->assertNotNull($reloaded->processedAt());
    }

    private function seedPayInsForMatching(): void
    {
        $datetimes = [
            '2026-01-01 08:00:00',
            '2026-01-01 09:00:00',
            '2026-01-01 10:00:00',
            '2026-01-01 11:00:00',
        ];

        foreach ($datetimes as $index => $datetime) {
            $payIn = PayIn::create(
                id: TransactionId::generate(),
                clientId: $this->client->id(),
                accountId: $this->account->id(),
                paymentMethodId: $this->method->id(),
                amount: Money::fromMinorUnits(1000, Currency::COP),
                fees: Money::zero(Currency::COP),
                reference: null,
                createdAt: new \DateTimeImmutable($datetime),
            );

            if ($index % 4 !== 3) {
                $payIn->markValidated();
                $payIn->markProcessing();
                $payIn->markProcessed(
                    $this->provider->id(),
                    ProviderTransactionId::fromString('FP-' . $index),
                    ProviderResponse::empty(),
                    new \DateTimeImmutable($datetime),
                );
            }

            $this->repository->save($payIn);
        }
    }
}
