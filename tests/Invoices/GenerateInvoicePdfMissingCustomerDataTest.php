<?php

declare(strict_types=1);

use Akira\PdfInvoices\Contracts\PdfGeneratorContract;
use Akira\PdfInvoices\DTO\InvoiceData as DtoInvoiceData;
use Akira\Sisp\Actions\GenerateInvoiceAction;
use Akira\Sisp\Actions\GenerateInvoicePdfAction;
use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Storage;

final class MissingCustomerDataPdfGenerator implements PdfGeneratorContract
{
    public ?DtoInvoiceData $lastInvoice = null;

    public function generate(DtoInvoiceData $invoice, string $template = 'modern'): string
    {
        $this->lastInvoice = $invoice;

        return '%PDF-NO-CUSTOMER%';
    }

    public function save(DtoInvoiceData $invoice, string $template = 'modern', ?string $path = null): string
    {
        return '%PDF-SAVED%';
    }
}

it('generates the invoice pdf when the transaction has no customer data', function (): void {
    $pdfGenerator = new MissingCustomerDataPdfGenerator();
    app()->instance(PdfGeneratorContract::class, $pdfGenerator);
    Storage::fake('public');

    $transaction = Transaction::factory()->create([
        'customer_name' => null,
        'customer_email' => null,
        'customer_address' => null,
        'customer_city' => null,
        'customer_country' => null,
        'customer_phone' => null,
    ]);

    $invoice = resolve(GenerateInvoiceAction::class)->handle($transaction);
    $invoice->update([
        'customer_name' => null,
        'customer_email' => null,
        'customer_address' => null,
        'customer_city' => null,
        'customer_country' => null,
    ]);

    $relativePath = resolve(GenerateInvoicePdfAction::class)->handle($invoice->refresh());

    Storage::disk('public')->assertExists($relativePath);
    expect($pdfGenerator->lastInvoice)->not->toBeNull();
});
