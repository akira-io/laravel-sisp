<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Enums\InvoiceStatus;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;

final readonly class UpdateInvoiceStatusAction
{
    public function handle(Transaction $transaction, TransactionStatus $status): void
    {
        $invoice = $transaction->invoice;

        if (! $invoice) {
            return;
        }

        $invoiceStatus = match ($status) {
            TransactionStatus::completed => InvoiceStatus::paid,
            TransactionStatus::failed => InvoiceStatus::cancelled,
            TransactionStatus::pending => InvoiceStatus::pending,
        };

        $invoice->update(['status' => $invoiceStatus->value]);
    }
}
