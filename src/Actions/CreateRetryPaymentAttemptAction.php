<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\PaymentRequest;
use Illuminate\Support\Facades\DB;

final readonly class CreateRetryPaymentAttemptAction
{
    public function __construct(
        private RetryPaymentAction $retryPayment,
        private CreateTransactionAttemptAction $createAttempt,
    ) {}

    public function handle(Transaction $transaction): PaymentRequest
    {
        /** @var Transaction $lockedTransaction */
        $lockedTransaction = DB::transaction(function () use ($transaction): Transaction {
            /** @var Transaction $lockedTransaction */
            $lockedTransaction = Transaction::query()
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            $this->ensureCurrentAttemptExists($lockedTransaction);

            return $lockedTransaction;
        }, attempts: 3);

        return $this->retryPayment->handle($lockedTransaction, rotateMerchantSession: false);
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

        $this->createAttempt->createFromTransaction($transaction);
    }
}
