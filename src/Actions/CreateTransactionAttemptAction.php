<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;
use Akira\Sisp\ValueObjects\PaymentRequest;

final readonly class CreateTransactionAttemptAction
{
    public function handle(Transaction $transaction, PaymentRequest $paymentRequest, bool $supersedeCurrent = false): TransactionAttempt
    {
        if ($supersedeCurrent) {
            $this->ensureCurrentAttemptExists($transaction);

            $transaction->attempts()
                ->whereNull('superseded_at')
                ->update(['superseded_at' => now()]);
        }

        return $transaction->attempts()->create([
            'attempt_number' => $this->nextAttemptNumber($transaction),
            'merchant_ref' => $paymentRequest->merchantRef,
            'merchant_session' => $paymentRequest->merchantSession,
            'status' => 'pending',
            'payload' => $paymentRequest->toArray(),
            'submitted_at' => now(),
        ]);
    }

    public function createFromTransaction(Transaction $transaction): TransactionAttempt
    {
        return $transaction->attempts()->create([
            'attempt_number' => $this->nextAttemptNumber($transaction),
            'merchant_ref' => $transaction->merchant_ref,
            'merchant_session' => $transaction->merchant_session,
            'status' => $transaction->status->value,
            'gateway_transaction_id' => $transaction->transaction_id,
            'message_type' => $transaction->message_type,
            'response_code' => $transaction->response_code,
            'merchant_response' => $transaction->merchant_response,
            'fingerprint' => $transaction->fingerprint,
            'payload' => $transaction->payload,
            'submitted_at' => $transaction->created_at,
            'callback_received_at' => $transaction->transaction_id !== null ? $transaction->updated_at : null,
        ]);
    }

    private function ensureCurrentAttemptExists(Transaction $transaction): void
    {
        $exists = $transaction->attempts()
            ->where('merchant_ref', $transaction->merchant_ref)
            ->where('merchant_session', $transaction->merchant_session)
            ->exists();

        if ($exists) {
            return;
        }

        $this->createFromTransaction($transaction);
    }

    private function nextAttemptNumber(Transaction $transaction): int
    {
        return ((int) $transaction->attempts()->max('attempt_number')) + 1;
    }
}
