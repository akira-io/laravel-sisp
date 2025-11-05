<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\PdfInvoices\Builder\EntityBuilder;
use Akira\PdfInvoices\Builder\InvoiceBuilder;
use Akira\PdfInvoices\Builder\ItemBuilder;
use Akira\PdfInvoices\Contracts\PdfGeneratorContract;
use Akira\Sisp\Models\Invoice;
use Illuminate\Support\Str;

final readonly class GenerateInvoicePdfAction
{
    public function handle(Invoice $invoice): string
    {
        $transaction = $invoice->transaction;

        $seller = EntityBuilder::make()
            ->name(config('sisp.invoice.company_name', config('app.name')))
            ->address(config('sisp.invoice.company_address', ''))
            ->vat(config('sisp.invoice.company_code', ''))
            ->email(config('sisp.invoice.company_email', ''))
            ->set('country', config('sisp.invoice.company_country', ''))
            ->set('phone', config('sisp.invoice.company_phone', ''))
            ->set('website', config('sisp.invoice.company_website', ''))
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
        $pdfGenerator = app(PdfGeneratorContract::class);
        $template = config('sisp.invoice.template', 'minimal');
        $pdfContent = $pdfGenerator->generate($invoiceData, $template);

        $filename = Str::uuid().'.pdf';
        $relativePath = "invoices/$filename";
        $storagePath = storage_path('app/public/'.$relativePath);

        if (! file_exists(dirname($storagePath))) {
            mkdir(dirname($storagePath), 0755, true);
        }

        file_put_contents($storagePath, $pdfContent);

        $invoice->update(['pdf_path' => $relativePath]);

        return $relativePath;
    }
}
