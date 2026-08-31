<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Actions\Generators\InvoiceNumberGeneratorAction;
use Akira\Sisp\Enums\InvoiceStatus;
use Akira\Sisp\Models\Invoice;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\InvoiceData;

final readonly class GenerateInvoiceAction
{
    public function __construct(
        private InvoiceNumberGeneratorAction $invoiceNumberGenerator,
    ) {}

    public function handle(Transaction $transaction): Invoice
    {
        $invoiceNumber = $this->invoiceNumberGenerator->handle($transaction);

        $invoiceData = InvoiceData::from([
            'invoice_number' => $invoiceNumber,
            'invoice_date' => now(),
            'due_date' => now()->addDays(7),
        ]);

        return Invoice::query()
            ->create([
                'transaction_id' => $transaction->id,
                'invoice_number' => $invoiceData->invoice_number,
                'invoice_date' => $invoiceData->invoice_date,
                'due_date' => $invoiceData->due_date,
                'status' => InvoiceStatus::pending->value,
                'customer_name' => $transaction->customer_name,
                'customer_email' => $transaction->customer_email,
                'customer_city' => $transaction->customer_city,
                'customer_address' => $transaction->customer_address,
                'customer_country' => $transaction->customer_country,
                'customer_vat' => $transaction->customer_vat,
                'customer_tax_name' => $transaction->customer_tax_name,
                'customer_tax_entity_type' => $transaction->customer_tax_entity_type,
                'customer_tax_address' => $transaction->customer_tax_address,
                'notes' => $invoiceData->notes,
                'metadata' => $invoiceData->metadata,
            ]);
    }
}
