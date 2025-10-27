<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Actions\Generators\InvoiceNumberGeneratorAction;
use Akira\Sisp\Enums\InvoiceStatus;
use Akira\Sisp\Models\Invoice;
use Akira\Sisp\Transaction;
use Akira\Sisp\ValueObjects\InvoiceData;

final readonly class GenerateInvoiceAction
{
    public function __construct(
        private InvoiceNumberGeneratorAction $invoiceNumberGenerator,
    ) {}

    public function handle(Transaction $transaction, InvoiceData $invoiceData): Invoice
    {
        $invoiceNumber = $this->invoiceNumberGenerator->handle($transaction);

        return Invoice::create([
            'transaction_id' => $transaction->id,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $invoiceData->invoice_date,
            'due_date' => $invoiceData->due_date,
            'status' => InvoiceStatus::pending->value,
            'notes' => $invoiceData->notes,
            'metadata' => $invoiceData->metadata,
        ]);
    }
}
