<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Events\TransactionCancelled;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\TransactionLogContext;
use Akira\Sisp\Support\TransactionRowLock;
use Illuminate\Support\Facades\DB;
use LogicException;

final readonly class CancelTransactionAction
{
    private UpdateInvoiceStatusAction $updateInvoiceStatus;

    public function __construct(?UpdateInvoiceStatusAction $updateInvoiceStatus = null)
    {
        $this->updateInvoiceStatus = $updateInvoiceStatus ?? resolve(UpdateInvoiceStatusAction::class);
    }

    public function handle(Transaction $transaction, string $reason = 'user_cancelled'): Transaction
    {
        DB::transaction(function () use ($transaction, $reason): void {
            $locked = TransactionRowLock::acquire($transaction);

            if (! $locked instanceof Transaction || $this->cannotBeCancelled($locked)) {
                $status = $locked instanceof Transaction ? $locked->status->value : $transaction->status->value;

                throw new LogicException("Transaction with status '{$status}' cannot be cancelled.");
            }

            TransactionRowLock::adopt($transaction, $locked);

            TransactionLogContext::run(
                'cancel',
                fn (): bool => $transaction->update([
                    'status' => TransactionStatus::cancelled->value,
                    'message_type' => 'cancelled',
                    'merchant_response' => $reason,
                    'cancelled_at' => now(),
                ])
            );

            $this->updateInvoiceStatus->handle($transaction, TransactionStatus::cancelled);
        });

        event(new TransactionCancelled($transaction, $reason));

        return $transaction;
    }

    private function cannotBeCancelled(Transaction $transaction): bool
    {
        return in_array($transaction->status->value, ['completed', 'cancelled'], true);
    }
}
