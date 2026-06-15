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
        $attempt = TransactionAttempt::query()
            ->with('transaction')
            ->where('merchant_ref', $payload->merchantRef)
            ->where('merchant_session', $payload->merchantSession)
            ->first();

        if ($attempt instanceof TransactionAttempt) {
            return $attempt;
        }

        return DB::transaction(function () use ($payload): TransactionAttempt {
            $transaction = Transaction::query()
                ->where('merchant_ref', $payload->merchantRef)
                ->where('merchant_session', $payload->merchantSession)
                ->lockForUpdate()
                ->firstOrFail();

            return $this->createAttempt->createFromTransaction($transaction);
        });
    }
}
