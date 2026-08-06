<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use PayIn\Application\Exception\PayInConcurrencyException;
use PayIn\Application\Exception\ReferenceAlreadyUsedException;
use PayIn\Domain\Contracts\PayInRepository;
use PayIn\Domain\Contracts\PayInSearchCriteria;
use PayIn\Domain\PayIn\PayIn;
use PayIn\Domain\PayIn\Reference;
use PayIn\Domain\PayIn\TransactionId;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\PayInMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Models\PayInModel;
use PayIn\Infrastructure\Persistence\Eloquent\Models\TransactionModel;

/**
 * Implementación Eloquent del puerto PayInRepository.
 *
 * El aggregate PayIn se persiste en dos tablas (transactions + pay_ins) de
 * forma atómica. Las actualizaciones utilizan locking optimista: el
 * repositorio recuerda la versión con la que se cargó cada aggregate
 * (compare-and-set) sin contaminar el dominio con conceptos de persistencia.
 */
final class EloquentPayInRepository implements PayInRepository
{
    /**
     * Versión cargada de cada aggregate, clave: id del PayIn.
     *
     * @var array<string, int>
     */
    private array $loadedVersions = [];

    public function __construct(private readonly PayInMapper $mapper)
    {
    }

    public function save(PayIn $payIn): void
    {
        $models = $this->mapper->toModels($payIn);
        $id = $payIn->id()->toString();

        if (!isset($this->loadedVersions[$id])) {
            $this->insert($models['transaction'], $models['payIn'], $payIn);
            $this->loadedVersions[$id] = 1;

            return;
        }

        $this->updateWithOptimisticLocking($models['transaction'], $models['payIn'], $payIn);
    }

    public function findById(TransactionId $id): ?PayIn
    {
        $transaction = TransactionModel::query()
            ->with('payIn')
            ->find($id->toString());

        if ($transaction === null || $transaction->payIn === null) {
            return null;
        }

        $this->loadedVersions[$transaction->id] = (int) $transaction->version;

        return $this->mapper->fromModels($transaction, $transaction->payIn);
    }

    public function existsByReference(Reference $reference): bool
    {
        return TransactionModel::query()
            ->where('reference', $reference->value())
            ->exists();
    }

    public function matching(PayInSearchCriteria $criteria): array
    {
        $query = TransactionModel::query()
            ->with('payIn')
            ->orderByDesc('created_at');

        $this->applyCriteria($query, $criteria);

        $payIns = [];

        foreach ($query->limit($criteria->limit)->offset($criteria->offset)->get() as $transaction) {
            $payInRow = $transaction->payIn;

            if ($payInRow === null) {
                continue;
            }

            $payIns[] = $this->mapper->fromModels($transaction, $payInRow);
        }

        return $payIns;
    }

    public function countMatching(PayInSearchCriteria $criteria): int
    {
        $query = TransactionModel::query();

        $this->applyCriteria($query, $criteria);

        return $query->count();
    }

    private function insert(TransactionModel $transaction, PayInModel $payIn, PayIn $aggregate): void
    {
        try {
            $transaction->save();
            $payIn->save();
        } catch (QueryException $exception) {
            if ($this->isReferenceViolation($exception) && $aggregate->reference() instanceof \PayIn\Domain\PayIn\Reference) {
                throw new ReferenceAlreadyUsedException($aggregate->reference()->value());
            }

            throw $exception;
        }
    }

    private function updateWithOptimisticLocking(
        TransactionModel $fresh,
        PayInModel $payIn,
        PayIn $aggregate,
    ): void {
        $id = $aggregate->id()->toString();
        $expectedVersion = $this->loadedVersions[$id];
        $newVersion = $expectedVersion + 1;

        $affected = TransactionModel::query()
            ->whereKey($id)
            ->where('version', $expectedVersion)
            ->update([
                'status' => $fresh->status,
                'provider_id' => $fresh->provider_id,
                'provider_transaction_id' => $fresh->provider_transaction_id,
                'provider_response' => $fresh->provider_response !== null ? json_encode($fresh->provider_response, JSON_THROW_ON_ERROR) : null,
                'error_code' => $fresh->error_code,
                'error_message' => $fresh->error_message,
                'processed_at' => $fresh->processed_at !== null ? $fresh->processed_at->format('Y-m-d H:i:s') : null,
                'updated_at' => now()->format('Y-m-d H:i:s'),
                'version' => $newVersion,
            ]);

        if ($affected === 0) {
            throw new PayInConcurrencyException($id);
        }

        $this->loadedVersions[$id] = $newVersion;

        PayInModel::query()->updateOrCreate(
            ['transaction_id' => $id],
            [
                'origin_account_id' => $payIn->origin_account_id,
                'account_id' => $payIn->account_id,
                'payment_method_id' => $payIn->payment_method_id,
                'fees' => $payIn->fees,
            ],
        );
    }

    private function isReferenceViolation(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'reference');
    }

    /**
     * @param Builder<TransactionModel> $query
     */
    private function applyCriteria(Builder $query, PayInSearchCriteria $criteria): void
    {
        if ($criteria->status instanceof \PayIn\Domain\PayIn\PayInStatus) {
            $query->where('status', $criteria->status->value);
        }

        if ($criteria->from instanceof \DateTimeImmutable) {
            $query->where('created_at', '>=', $criteria->from);
        }

        if ($criteria->to instanceof \DateTimeImmutable) {
            $query->where('created_at', '<=', $criteria->to);
        }
    }
}
