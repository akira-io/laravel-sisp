<?php

declare(strict_types=1);

use Akira\PdfInvoices\Contracts\PdfGeneratorContract;
use Akira\PdfInvoices\DTO\InvoiceData as DtoInvoiceData;
use Akira\Sisp\Actions\GenerateInvoiceAction;
use Akira\Sisp\Actions\GenerateInvoicePdfAction;
use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Storage;

final class CapturingPdfGenerator implements PdfGeneratorContract
{
    public ?DtoInvoiceData $lastInvoice = null;

    public function generate(DtoInvoiceData $invoice, string $template = 'modern'): string
    {
        $this->lastInvoice = $invoice;

        return '%PDF-DUE-DATE%';
    }

    public function save(DtoInvoiceData $invoice, string $template = 'modern', ?string $path = null): string
    {
        return '%PDF-SAVED%';
    }
}

it('generates invoice pdf when due date is null', function (): void {
    $pdfGenerator = new CapturingPdfGenerator();
    app()->instance(PdfGeneratorContract::class, $pdfGenerator);
    Storage::fake('public');

    $transaction = Transaction::factory()->create();
    $invoice = resolve(GenerateInvoiceAction::class)->handle($transaction);
    $invoice->update(['due_date' => null]);

    $relativePath = resolve(GenerateInvoicePdfAction::class)->handle($invoice);

    Storage::disk('public')->assertExists($relativePath);
    expect($pdfGenerator->lastInvoice)->not->toBeNull()
        ->and($pdfGenerator->lastInvoice?->dueAt)->toBeNull();
});

it('passes due date to invoice builder when due date is present', function (): void {
    $pdfGenerator = new CapturingPdfGenerator();
    app()->instance(PdfGeneratorContract::class, $pdfGenerator);
    Storage::fake('public');

    $transaction = Transaction::factory()->create();
    $invoice = resolve(GenerateInvoiceAction::class)->handle($transaction);

    resolve(GenerateInvoicePdfAction::class)->handle($invoice);

    expect($pdfGenerator->lastInvoice)->not->toBeNull()
        ->and($pdfGenerator->lastInvoice?->dueAt?->toDateString())->toBe($invoice->due_date?->toDateString());
});

it('stores generated invoice pdfs in the configured path', function (): void {
    $pdfGenerator = new CapturingPdfGenerator();
    app()->instance(PdfGeneratorContract::class, $pdfGenerator);
    Storage::fake('public');
    config()->set('sisp.invoice.path', 'billing/pdfs');

    $transaction = Transaction::factory()->create();
    $invoice = resolve(GenerateInvoiceAction::class)->handle($transaction);

    $relativePath = resolve(GenerateInvoicePdfAction::class)->handle($invoice);

    Storage::disk('public')->assertExists($relativePath);
    expect(str_starts_with($relativePath, 'billing/pdfs/'))->toBeTrue()
        ->and($invoice->refresh()->pdf_path)->toBe($relativePath);
});
