<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;
use Akira\Sisp\ValueObjects\PaymentRequest;

final readonly class CreateTransactionAttemptAction
{
    private const string EMPTY_ATTEMPT_SESSION_BASE = 'legacy-empty-session';

    public function handle(
        Transaction $transaction,
        PaymentRequest $paymentRequest,
        bool $supersedeCurrent = false,
        ?string $attemptSession = null,
    ): TransactionAttempt {
        if ($supersedeCurrent) {
            $this->ensureCurrentAttemptExists($transaction);

            $transaction->attempts()
                ->whereNull('superseded_at')
                ->update(['superseded_at' => now()]);
        }

        return TransactionAttempt::query()->create([
            'transaction_id' => $transaction->id,
            'attempt_number' => $this->nextAttemptNumber($transaction),
            'merchant_ref' => $paymentRequest->merchantRef,
            'merchant_session' => $paymentRequest->merchantSession,
            'attempt_session' => $this->attemptSession($attemptSession ?? $paymentRequest->merchantSession, $transaction),
            'status' => TransactionStatus::pending,
            'payload' => $paymentRequest->toArray(),
            'submitted_at' => now(),
        ]);
    }

    public function createFromTransaction(Transaction $transaction): TransactionAttempt
    {
        return TransactionAttempt::query()->create([
            'transaction_id' => $transaction->id,
            'attempt_number' => $this->nextAttemptNumber($transaction),
            'merchant_ref' => $transaction->merchant_ref,
            'merchant_session' => $transaction->merchant_session,
            'attempt_session' => $this->attemptSession($transaction->merchant_session, $transaction),
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
        return ((int) $transaction->attempts()->lockForUpdate()->max('attempt_number')) + 1;
    }

    private function attemptSession(?string $attemptSession, Transaction $transaction): string
    {
        if ($attemptSession !== null && $attemptSession !== '') {
            return $attemptSession;
        }

        return $this->legacyIdentifier(self::EMPTY_ATTEMPT_SESSION_BASE, (int) $transaction->id);
    }

    private function legacyIdentifier(string $value, int $transactionId): string
    {
        $suffix = '-legacy-'.$transactionId;

        return mb_substr($value, 0, 255 - mb_strlen($suffix)).$suffix;
    }
}
