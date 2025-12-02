<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\PdfInvoices\Builder\EntityBuilder;
use Akira\PdfInvoices\Builder\InvoiceBuilder;
use Akira\PdfInvoices\Builder\ItemBuilder;
use Akira\PdfInvoices\Contracts\PdfGeneratorContract;
use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Models\Invoice;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final readonly class GenerateInvoicePdfAction
{
    public function __construct(
        private LoadConfig $config,
    ) {}

    public function handle(Invoice $invoice): string
    {
        $transaction = $invoice->transaction;

        $seller = EntityBuilder::make()
            ->name($this->config->getInvoiceCompanyName() ?: config('app.name'))
            ->address($this->config->getInvoiceCompanyAddress())
            ->vat($this->config->getInvoiceCompanyCode())
            ->email($this->config->getInvoiceCompanyEmail())
            ->set('country', $this->config->getInvoiceCompanyCountry())
            ->set('phone', $this->config->getInvoiceCompanyPhone())
            ->set('website', $this->config->getInvoiceCompanyWebsite())
            ->build();

        $buyer = EntityBuilder::make()
            ->name($invoice->customer_name ?? $transaction->customer_name)
            ->email($invoice->customer_email ?? $transaction->customer_email)
            ->address($invoice->customer_address ?? $transaction->customer_address)
            ->set('country', $invoice->customer_country ?? $transaction->customer_country)
            ->set('phone', $transaction->customer_phone)

            ->set('city', $invoice->customer_city ?? $transaction->customer_city)
            ->build();

        $invoiceBuilder = InvoiceBuilder::make()
            ->seller($seller)
            ->buyer($buyer)
            ->invoiceNumber($invoice->invoice_number)
            ->issuedAt($invoice->invoice_date)
            ->dueAt($invoice->due_date)
            ->currency('ECV');

        foreach ($transaction->items as $item) {
            $itemData = ItemBuilder::make()
                ->description($item->product_name)
                ->unitPrice($item->unit_price_cents / 100)
                ->quantity($item->quantity)
                ->build();

            $invoiceBuilder->addItem($itemData);
        }

        $invoiceData = $invoiceBuilder->build();
        $pdfGenerator = resolve(PdfGeneratorContract::class);
        $template = $this->config->getInvoiceTemplate();
        $pdfContent = $pdfGenerator->generate($invoiceData, $template);

        $filename = Str::uuid().'.pdf';
        $storageDisk = $this->config->getInvoiceStorageDisk();
        $relativePath = "invoices/$filename";

        Storage::disk($storageDisk)->put($relativePath, $pdfContent);

        $invoice->update(['pdf_path' => $relativePath]);

        return $relativePath;
    }
}
