<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Transaction;

use Akira\Sisp\Actions\CreateTransactionAttemptAction;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Illuminate\Support\Facades\DB;

final readonly class FindTransactionAttemptAction
{
    public function __construct(private CreateTransactionAttemptAction $createAttempt) {}

    public function handle(CallbackPayload $payload): TransactionAttempt
    {
        $attempt = $this->findAttempt($payload);

        if ($attempt instanceof TransactionAttempt) {
            return $attempt;
        }

        return DB::transaction(function () use ($payload): TransactionAttempt {
            $transaction = Transaction::query()
                ->where('merchant_ref', $payload->merchantRef)
                ->where('merchant_session', $payload->merchantSession)
                ->lockForUpdate()
                ->firstOrFail();

            $attempt = $this->findAttempt($payload, lockForUpdate: true);

            if ($attempt instanceof TransactionAttempt) {
                return $attempt;
            }

            $attempt = $this->createAttempt->createFromTransaction($transaction);
            $attempt->setRelation('transaction', $transaction);

            return $attempt;
        });
    }

    private function findAttempt(CallbackPayload $payload, bool $lockForUpdate = false): ?TransactionAttempt
    {
        $query = TransactionAttempt::query()
            ->with('transaction')
            ->where('merchant_ref', $payload->merchantRef)
            ->where('merchant_session', $payload->merchantSession);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }
}
