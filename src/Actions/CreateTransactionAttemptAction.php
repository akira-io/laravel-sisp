<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;
use Akira\Sisp\ValueObjects\PaymentRequest;

final readonly class CreateTransactionAttemptAction
{
    public function handle(Transaction $transaction, PaymentRequest $paymentRequest, bool $supersedeCurrent = false): TransactionAttempt
    {
        $transaction->newQuery()->whereKey($transaction->getKey())->lockForUpdate()->first();
        $attemptNumber = ((int) $transaction->attempts()->max('attempt_number')) + 1;

        if ($supersedeCurrent) {
            $transaction->attempts()
                ->whereNull('superseded_at')
                ->update(['superseded_at' => now()]);
        }

        return TransactionAttempt::query()->create([
            'transaction_id' => $transaction->id,
            'attempt_number' => $attemptNumber,
            'merchant_ref' => $paymentRequest->merchantRef,
            'merchant_session' => $paymentRequest->merchantSession,
            'status' => TransactionStatus::pending,
            'payload' => $paymentRequest->toArray(),
            'submitted_at' => now(),
        ]);
    }

    public function createFromTransaction(Transaction $transaction): TransactionAttempt
    {
        $transaction->newQuery()->whereKey($transaction->getKey())->lockForUpdate()->first();
        $attemptNumber = ((int) $transaction->attempts()->max('attempt_number')) + 1;

        return TransactionAttempt::query()->create([
            'transaction_id' => $transaction->id,
            'attempt_number' => $attemptNumber,
            'merchant_ref' => $transaction->merchant_ref,
            'merchant_session' => $transaction->merchant_session,
            'status' => $transaction->status,
            'gateway_transaction_id' => $transaction->transaction_id,
            'message_type' => $transaction->message_type,
            'response_code' => $transaction->response_code,
            'merchant_response' => $transaction->merchant_response,
            'fingerprint' => $transaction->fingerprint,
            'payload' => $transaction->payload,
            'submitted_at' => $transaction->created_at ?? now(),
        ]);
    }
}
