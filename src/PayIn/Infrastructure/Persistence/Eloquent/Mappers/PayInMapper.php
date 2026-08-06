<?php

declare(strict_types=1);

namespace PayIn\Infrastructure\Persistence\Eloquent\Mappers;

use Illuminate\Support\Carbon;
use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Client\ClientId;
use PayIn\Domain\Currency;
use PayIn\Domain\Money;
use PayIn\Domain\PayIn\PayIn;
use PayIn\Domain\PayIn\PayInStatus;
use PayIn\Domain\PayIn\ProviderResponse;
use PayIn\Domain\PayIn\ProviderTransactionId;
use PayIn\Domain\PayIn\Reference;
use PayIn\Domain\PayIn\TransactionId;
use PayIn\Domain\PayIn\TransactionType;
use PayIn\Domain\PaymentMethod\PaymentMethodId;
use PayIn\Domain\PaymentProvider\ProviderId;
use PayIn\Infrastructure\Persistence\Eloquent\Models\PayInModel;
use PayIn\Infrastructure\Persistence\Eloquent\Models\TransactionModel;

/**
 * Traduce entre el aggregate PayIn y sus dos representaciones persistentes
 * (transactions + pay_ins).
 */
final class PayInMapper
{
    /**
     * @return array{transaction: TransactionModel, payIn: PayInModel}
     */
    public function toModels(PayIn $payIn): array
    {
        $transaction = new TransactionModel();
        $transaction->id = $payIn->id()->toString();
        $transaction->type = $payIn->type()->value;
        $transaction->client_id = $payIn->clientId()->toString();
        $transaction->amount = $payIn->amount()->minorUnits();
        $transaction->currency = $payIn->amount()->currency()->value;
        $transaction->status = $payIn->status()->value;
        $transaction->reference = $payIn->reference()?->value();
        $transaction->provider_id = $payIn->providerId()?->toString();
        $transaction->provider_transaction_id = $payIn->providerTransactionId()?->value();
        $transaction->provider_response = $payIn->providerResponse()?->toArray();
        $transaction->error_code = $payIn->errorCode();
        $transaction->error_message = $payIn->errorMessage();
        $transaction->created_at = Carbon::instance($payIn->createdAt());
        $transaction->processed_at = $payIn->processedAt() instanceof \DateTimeImmutable
            ? Carbon::instance($payIn->processedAt())
            : null;

        $payInRow = new PayInModel();
        $payInRow->transaction_id = $payIn->id()->toString();
        $payInRow->account_id = $payIn->accountId()->toString();
        $payInRow->payment_method_id = $payIn->paymentMethodId()->toString();
        $payInRow->fees = $payIn->fees()->minorUnits();

        return ['transaction' => $transaction, 'payIn' => $payInRow];
    }

    public function fromModels(TransactionModel $transaction, PayInModel $payIn): PayIn
    {
        $currency = Currency::fromCode($transaction->currency);

        return PayIn::reconstitute(
            id: TransactionId::fromString($transaction->id),
            clientId: ClientId::fromString($transaction->client_id),
            accountId: AccountId::fromString($payIn->account_id),
            paymentMethodId: PaymentMethodId::fromString($payIn->payment_method_id),
            amount: Money::fromMinorUnits((int) $transaction->amount, $currency),
            fees: Money::fromMinorUnits((int) $payIn->fees, $currency),
            type: TransactionType::from($transaction->type),
            createdAt: new \DateTimeImmutable($transaction->created_at->format('Y-m-d H:i:s.u')),
            status: PayInStatus::from($transaction->status),
            reference: $transaction->reference !== null ? Reference::fromString($transaction->reference) : null,
            providerId: $transaction->provider_id !== null ? ProviderId::fromString($transaction->provider_id) : null,
            providerTransactionId: $transaction->provider_transaction_id !== null
                ? ProviderTransactionId::fromString($transaction->provider_transaction_id)
                : null,
            providerResponse: $transaction->provider_response !== null
                ? ProviderResponse::fromArray($transaction->provider_response)
                : null,
            errorCode: $transaction->error_code,
            errorMessage: $transaction->error_message,
            processedAt: $transaction->processed_at !== null
                ? new \DateTimeImmutable($transaction->processed_at->format('Y-m-d H:i:s.u'))
                : null,
        );
    }
}
