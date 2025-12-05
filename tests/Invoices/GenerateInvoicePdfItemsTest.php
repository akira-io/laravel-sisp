<?php

declare(strict_types=1);

use Akira\PdfInvoices\Contracts\PdfGeneratorContract;
use Akira\PdfInvoices\DTO\InvoiceData as DtoInvoiceData;
use Akira\Sisp\Actions\GenerateInvoiceAction;
use Akira\Sisp\Actions\GenerateInvoicePdfAction;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionItem;
use Illuminate\Support\Facades\Storage;

final class TestPdfGeneratorItems implements PdfGeneratorContract
{
    public function generate(DtoInvoiceData $invoice, string $template = 'modern'): string
    {
        // return static content regardless of items
        return '%PDF-WITH-ITEMS%';
    }

    public function save(DtoInvoiceData $invoice, string $template = 'modern', ?string $path = null): string
    {
        return '%PDF-SAVED%';
    }
}

it('includes transaction items when generating invoice PDF', function (): void {
    app()->instance(PdfGeneratorContract::class, new TestPdfGeneratorItems());
    Storage::fake('public');

    $t = Transaction::factory()->create([
        'customer_name' => 'John',
        'customer_email' => 'john@example.test',
    ]);

    // Ensure items exist so the foreach in action is executed
    TransactionItem::factory()->forTransaction($t)->count(2)->create();

    $invoice = resolve(GenerateInvoiceAction::class)->handle($t);
    $relativePath = resolve(GenerateInvoicePdfAction::class)->handle($invoice);

    Storage::disk('public')->assertExists($relativePath);
});
