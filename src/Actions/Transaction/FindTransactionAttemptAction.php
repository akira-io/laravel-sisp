<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Transaction;

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Illuminate\Support\Facades\DB;

final readonly class FindTransactionAttemptAction
{
    public function handle(CallbackPayload $payload): TransactionAttempt
    {
        $attempt = $this->findAttempt($payload);

        if ($attempt instanceof TransactionAttempt) {
            return $attempt;
        }

        $transaction = Transaction::query()
            ->where('merchant_ref', $payload->merchantRef)
            ->where('merchant_session', $payload->merchantSession)
            ->firstOrFail();

        return DB::transaction(function () use ($payload, $transaction): TransactionAttempt {
            $attempt = $this->findAttempt($payload);

            if ($attempt instanceof TransactionAttempt) {
                return $attempt;
            }

            /** @var Transaction $lockedTransaction */
            $lockedTransaction = Transaction::query()
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            return $lockedTransaction->attempts()->create([
                'attempt_number' => ((int) $lockedTransaction->attempts()->max('attempt_number')) + 1,
                'merchant_ref' => $lockedTransaction->merchant_ref,
                'merchant_session' => $lockedTransaction->merchant_session,
                'status' => $lockedTransaction->status->value,
                'gateway_transaction_id' => $lockedTransaction->transaction_id,
                'message_type' => $lockedTransaction->message_type,
                'response_code' => $lockedTransaction->response_code,
                'merchant_response' => $lockedTransaction->merchant_response,
                'fingerprint' => $lockedTransaction->fingerprint,
                'payload' => $lockedTransaction->payload,
                'submitted_at' => $lockedTransaction->created_at,
                'callback_received_at' => $lockedTransaction->transaction_id !== null ? $lockedTransaction->updated_at : null,
            ]);
        });
    }

    private function findAttempt(CallbackPayload $payload): ?TransactionAttempt
    {
        return TransactionAttempt::query()
            ->where('merchant_ref', $payload->merchantRef)
            ->where('merchant_session', $payload->merchantSession)
            ->first();
    }
}
