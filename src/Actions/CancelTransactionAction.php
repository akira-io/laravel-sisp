<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Events\TransactionCancelled;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\TransactionLogContext;
use Illuminate\Support\Facades\DB;
use LogicException;

final readonly class CancelTransactionAction
{
    public function handle(Transaction $transaction, string $reason = 'user_cancelled'): Transaction
    {
        DB::transaction(function () use ($transaction, $reason): void {
            $locked = Transaction::query()->lockForUpdate()->find($transaction->id);

            if (! $locked instanceof Transaction || $this->cannotBeCancelled($locked)) {
                $status = $locked instanceof Transaction ? $locked->status->value : $transaction->status->value;

                throw new LogicException("Transaction with status '{$status}' cannot be cancelled.");
            }

            TransactionLogContext::run(
                'cancel',
                fn (): bool => $transaction->update([
                    'status' => TransactionStatus::cancelled->value,
                    'message_type' => 'cancelled',
                    'merchant_response' => $reason,
                    'cancelled_at' => now(),
                ])
            );
        });

        event(new TransactionCancelled($transaction, $reason));

        return $transaction;
    }

    private function cannotBeCancelled(Transaction $transaction): bool
    {

        return in_array($transaction->status->value, ['completed', 'cancelled', 'failed', 'refunded'], true);
    }
}
