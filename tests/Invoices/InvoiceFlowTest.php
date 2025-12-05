<?php

declare(strict_types=1);

use Akira\PdfInvoices\Contracts\PdfGeneratorContract;
use Akira\PdfInvoices\DTO\InvoiceData as DtoInvoiceData;
use Akira\Sisp\Actions\GenerateInvoiceAction;
use Akira\Sisp\Actions\GenerateInvoicePdfAction;
use Akira\Sisp\Actions\UpdateInvoiceStatusAction;
use Akira\Sisp\Enums\InvoiceStatus;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Invoice;
use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Storage;

final class TestPdfGenerator implements PdfGeneratorContract
{
    public function generate(DtoInvoiceData $invoice, string $template = 'modern'): string
    {
        return '%PDF-FAKE%';
    }

    public function save(DtoInvoiceData $invoice, string $template = 'modern', ?string $path = null): string
    {
        return '%PDF-SAVED%';
    }
}

it('generates invoice and pdf, updates invoice status', function (): void {
    config()->set('sisp.invoice.company_name', 'ACME');
    config()->set('sisp.invoice.company_address', 'Main Street');
    config()->set('sisp.invoice.company_code', 'TAX-1');
    config()->set('sisp.invoice.company_email', 'billing@acme.test');
    config()->set('sisp.invoice.company_country', 'AO');
    config()->set('sisp.invoice.company_phone', '+244...');
    config()->set('sisp.invoice.company_website', 'https://acme.test');
    // Bind a simple PDF generator via container (sem mocks)
    app()->instance(PdfGeneratorContract::class, new TestPdfGenerator());
    Storage::fake('public');

    $t = Transaction::factory()->create([
        'status' => 'pending',
        'customer_name' => 'Jane',
        'customer_email' => 'jane@example.test',
    ]);

    $invoice = resolve(GenerateInvoiceAction::class)->handle($t);
    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->status)->toBe(InvoiceStatus::pending);

    $relativePath = resolve(GenerateInvoicePdfAction::class)->handle($invoice);
    Storage::disk('public')->assertExists($relativePath);
    $invoice->refresh();
    expect($invoice->pdf_url)->not->toBeNull();

    // Update status mapping
    resolve(UpdateInvoiceStatusAction::class)->handle($invoice->transaction, TransactionStatus::completed);
    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::paid);
});
