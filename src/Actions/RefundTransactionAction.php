<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Enums\TransactionStatus;
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
                "Transaction with status '{$transaction->status->value}' cannot be refunded."
            );
        }

        if ($refundAmount > $transaction->amount) {
            throw new LogicException(
                "Refund amount ({$refundAmount}) cannot exceed transaction amount ({$transaction->amount})."
            );
        }

        throw_if($refundAmount <= 0, LogicException::class, 'Refund amount must be greater than 0.');

        $newAmount = $transaction->amount - $refundAmount;

        $transaction->update([
            'status' => TransactionStatus::refunded->value,
            'amount' => $newAmount,
            'merchant_response' => "{$reason}::{$refundAmount}",
            'refunded_at' => now(),
        ]);

        event(new TransactionRefunded($transaction, $refundAmount, $reason));

        return $transaction;
    }

    private function canBeRefunded(Transaction $transaction): bool
    {
        return $transaction->status->value === 'completed';
    }
}
