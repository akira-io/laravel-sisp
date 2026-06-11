<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Enums\InvoiceStatus;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Invoice;
use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class UpdateInvoiceStatusAction
{
    public function __construct(
        private GenerateInvoicePdfAction $generateInvoicePdf,
    ) {}

    public function handle(Transaction $transaction, TransactionStatus $status): void
    {
        /** @var Invoice|null $invoice */
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

        if ($status === TransactionStatus::completed && ! $invoice->pdf_path) {
            $this->generatePdfQuietly($invoice, $transaction);
        }
    }

    /**
     * PDF generation must never fail the payment flow: the transaction is
     * already completed at this point and missing PDF files can be recovered
     * later with the regenerate command.
     */
    private function generatePdfQuietly(Invoice $invoice, Transaction $transaction): void
    {
        try {
            $this->generateInvoicePdf->handle($invoice);
        } catch (Throwable $exception) {
            Log::error('SISP invoice PDF generation failed.', [
                'invoice_id' => $invoice->getKey(),
                'transaction_id' => $transaction->getKey(),
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
