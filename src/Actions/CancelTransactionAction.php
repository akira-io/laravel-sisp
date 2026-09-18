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
    private UpdateInvoiceStatusAction $updateInvoiceStatus;

    public function __construct(?UpdateInvoiceStatusAction $updateInvoiceStatus = null)
    {
        $this->updateInvoiceStatus = $updateInvoiceStatus ?? resolve(UpdateInvoiceStatusAction::class);
    }

    public function handle(Transaction $transaction, string $reason = 'user_cancelled'): Transaction
    {
        $cancelled = DB::transaction(function () use ($transaction, $reason): Transaction {
            $locked = $transaction->newQuery()->whereKey($transaction->getKey())->lockForUpdate()->first();

            if (! $locked instanceof Transaction || $this->cannotBeCancelled($locked)) {
                $status = $locked instanceof Transaction ? $locked->status->value : $transaction->status->value;

                throw new LogicException("Transaction with status '{$status}' cannot be cancelled.");
            }

            TransactionLogContext::run(
                'cancel',
                fn (): bool => $locked->update([
                    'status' => TransactionStatus::cancelled->value,
                    'message_type' => 'cancelled',
                    'merchant_response' => $reason,
                    'cancelled_at' => now(),
                ])
            );

            $this->updateInvoiceStatus->handle($locked, TransactionStatus::cancelled);

            return $locked;
        });

        $transaction->setRawAttributes($cancelled->getAttributes(), true);

        event(new TransactionCancelled($transaction, $reason));

        return $transaction;
    }

    private function cannotBeCancelled(Transaction $transaction): bool
    {
        return in_array($transaction->status->value, ['completed', 'cancelled'], true);
    }
}
