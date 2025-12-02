<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Events\TransactionRefunded;
use Akira\Sisp\Models\Transaction;
use LogicException;

final readonly class RefundTransactionAction
{
    public function handle(
        Transaction $transaction,
        float $refundAmount,
        string $reason = 'user_refund',
    ): Transaction {
        if (! $this->canBeRefunded($transaction)) {
            throw new LogicException(
                "Transaction with status '{$transaction->status}' cannot be refunded."
            );
        }

        if ($refundAmount > $transaction->amount) {
            throw new LogicException(
                "Refund amount ({$refundAmount}) cannot exceed transaction amount ({$transaction->amount})."
            );
        }

        throw_if($refundAmount <= 0, LogicException::class, 'Refund amount must be greater than 0.');

        $newAmount = $transaction->amount - $refundAmount;
        $status = $newAmount === 0 ? 'refunded' : 'partially_refunded';

        $transaction->update([
            'status' => $status,
            'amount' => $newAmount,
            'merchant_response' => "{$reason}::{$refundAmount}",
            'refunded_at' => now(),
        ]);

        event(new \Akira\Sisp\Events\TransactionRefunded($transaction, $refundAmount, $reason));

        return $transaction;
    }

    private function canBeRefunded(Transaction $transaction): bool
    {
        return in_array($transaction->status, [
            'completed',
            'partially_refunded',
        ]);
    }
}
