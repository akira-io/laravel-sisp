<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Events\TransactionCancelled;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\TransactionLogContext;
use LogicException;

final readonly class CancelTransactionAction
{
    public function handle(Transaction $transaction, string $reason = 'user_cancelled'): Transaction
    {

        if ($this->cannotBeCancelled($transaction)) {
            throw new LogicException(
                "Transaction with status '{$transaction->status->value}' cannot be cancelled."
            );
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

        event(new TransactionCancelled($transaction, $reason));

        return $transaction;
    }

    private function cannotBeCancelled(Transaction $transaction): bool
    {

        return in_array($transaction->status->value, ['completed', 'cancelled'], true);
    }
}
