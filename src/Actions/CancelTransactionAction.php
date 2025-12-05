<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Events\TransactionCancelled;
use Akira\Sisp\Models\Transaction;
use LogicException;

final readonly class CancelTransactionAction
{
    public function handle(Transaction $transaction, string $reason = 'user_cancelled'): Transaction
    {
        $status = is_object($transaction->status) && property_exists($transaction->status, 'value')
            ? $transaction->status->value
            : (string) $transaction->status;

        if ($this->cannotBeCancelled($transaction)) {
            throw new LogicException(
                "Transaction with status '{$status}' cannot be cancelled."
            );
        }

        $transaction->update([
            'status' => 'cancelled',
            'message_type' => 'cancelled',
            'merchant_response' => $reason,
            'cancelled_at' => now(),
        ]);

        event(new TransactionCancelled($transaction, $reason));

        return $transaction;
    }

    private function cannotBeCancelled(Transaction $transaction): bool
    {

        return in_array($transaction->status->value, ['completed', 'cancelled'], true);
    }
}
