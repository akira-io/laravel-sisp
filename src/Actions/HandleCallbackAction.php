<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Actions\Transaction\FindTransactionAttemptAction;
use Akira\Sisp\Actions\Transaction\MapTransactionStatusAction;
use Akira\Sisp\Actions\Transaction\ShouldPropagateAttemptCallbackAction;
use Akira\Sisp\Actions\Transaction\UpdateTransactionAction;
use Akira\Sisp\Actions\Transaction\UpdateTransactionAttemptAction;
use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Contracts\SispCredentialsResolver;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Events\PaymentCompleted;
use Akira\Sisp\Events\PaymentFailed;
use Akira\Sisp\Events\PaymentPending;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;
use Akira\Sisp\Support\SispAmount;
use Akira\Sisp\Support\TransactionLogContext;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Illuminate\Support\Facades\DB;

final readonly class HandleCallbackAction
{
    public function __construct(
        private FindTransactionAttemptAction $findTransactionAttempt,
        private UpdateTransactionAction $updateTransaction,
        private UpdateTransactionAttemptAction $updateAttempt,
        private MapTransactionStatusAction $mapStatus,
        private ShouldPropagateAttemptCallbackAction $shouldPropagateAttemptCallback,
        private SispCredentialsResolver $credentialsResolver,
        private LoadConfig $config,
    ) {}

    public function handle(CallbackPayload $payload): Transaction
    {
        $attempt = $this->findTransactionAttempt->handle($payload);

        $transaction = $attempt->transaction()->firstOrFail();

        if (! Sisp::validateCallback($payload)) {
            $this->failTransaction($transaction, $attempt, $payload, 'invalid_callback_fingerprint');

            event(new PaymentFailed($transaction, $payload));

            return $transaction;
        }

        if (! $this->matchesTransaction($transaction, $attempt, $payload)) {
            $this->failTransaction($transaction, $attempt, $payload, 'callback_details_mismatch');

            event(new PaymentFailed($transaction, $payload));

            return $transaction;
        }

        $status = $this->mapStatus->handle($payload->messageType);

        $this->updateTransaction->handle($transaction, $payload, $attempt);

        if (! $this->shouldPropagateAttemptCallback->handle($attempt, $status)) {
            return $transaction;
        }

        $this->dispatchEvent($transaction->refresh(), $payload);

        return $transaction;
    }

    private function dispatchEvent(Transaction $transaction, CallbackPayload $payload): void
    {
        match ($transaction->status) {
            TransactionStatus::completed => event(new PaymentCompleted($transaction, $payload)),
            TransactionStatus::failed => event(new PaymentFailed($transaction, $payload)),
            TransactionStatus::pending => event(new PaymentPending($transaction, $payload)),
            default => null, // @codeCoverageIgnore
        };
    }

    private function matchesTransaction(Transaction $transaction, TransactionAttempt $attempt, CallbackPayload $payload): bool
    {
        return $attempt->merchant_ref === $payload->merchantRef
            && $attempt->merchant_session === $payload->merchantSession
            && $this->amountMatches($this->transactionAmount($transaction), $payload->amount)
            && (! $payload->currencyProvided || $this->transactionString($transaction, 'currency') === $payload->currency)
            && (! $payload->transactionCodeProvided || $this->transactionCode($transaction) === $payload->transactionCode)
            && (! $payload->posIDProvided || $this->credentialsResolver->resolve()->posId === $payload->posID);
    }

    private function amountMatches(float|int|string $expected, float|int|string $actual): bool
    {
        return SispAmount::toThousandths($expected) === SispAmount::toThousandths($actual);
    }

    private function failTransaction(Transaction $transaction, TransactionAttempt $attempt, CallbackPayload $payload, string $merchantResponse): void
    {
        DB::transaction(function () use ($attempt, $merchantResponse, $payload, $transaction): void {
            $this->updateAttempt->handle($attempt, $payload, TransactionStatus::failed, $merchantResponse);

            if (! $this->shouldPropagateAttemptCallback->handle($attempt, TransactionStatus::failed)) {
                return;
            }

            TransactionLogContext::run(
                'callback',
                fn (): bool => $transaction->update([
                    'merchant_ref' => $attempt->merchant_ref,
                    'merchant_session' => $attempt->merchant_session,
                    'transaction_id' => $payload->transactionID,
                    'message_type' => $payload->messageType,
                    'merchant_response' => $merchantResponse,
                    'response_code' => $payload->merchantRespCp,
                    'fingerprint' => $payload->fingerprint,
                    'status' => TransactionStatus::failed,
                ])
            );
        });
    }

    private function transactionAmount(Transaction $transaction): float|int|string
    {
        $amount = $transaction->getAttribute('amount');

        return is_float($amount) || is_int($amount) || is_string($amount) ? $amount : 0;
    }

    private function transactionString(Transaction $transaction, string $attribute): string
    {
        $value = $transaction->getAttribute($attribute);

        return is_string($value) ? $value : '';
    }

    private function transactionCode(Transaction $transaction): string
    {
        $transactionCode = $this->transactionString($transaction, 'transaction_code');

        return $transactionCode === '' ? $this->config->getDefaultTransactionCode() : $transactionCode;
    }
}
