<?php

declare(strict_types=1);

use Akira\PdfInvoices\Contracts\PdfGeneratorContract;
use Akira\PdfInvoices\DTO\InvoiceData as DtoInvoiceData;
use Akira\Sisp\Actions\GenerateInvoiceAction;
use Akira\Sisp\Actions\UpdateInvoiceStatusAction;
use Akira\Sisp\Enums\InvoiceStatus;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class UpdateInvoiceStatusTestPdfGenerator implements PdfGeneratorContract
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

it('updates invoice status to pending when transaction is pending', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending']);
    $invoice = resolve(GenerateInvoiceAction::class)->handle($transaction);

    $action = resolve(UpdateInvoiceStatusAction::class);
    $action->handle($invoice->transaction, TransactionStatus::pending);

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::pending)
        ->and($invoice->pdf_path)->toBeNull();
});

it('updates invoice status to cancelled when transaction fails', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'failed']);
    $invoice = resolve(GenerateInvoiceAction::class)->handle($transaction);

    $action = resolve(UpdateInvoiceStatusAction::class);
    $action->handle($invoice->transaction, TransactionStatus::failed);

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::cancelled)
        ->and($invoice->pdf_path)->toBeNull();
});

it('generates pdf when transaction completes and pdf does not exist', function (): void {
    config()->set('sisp.invoice.company_name', 'ACME');
    config()->set('sisp.invoice.company_address', 'Main Street');
    config()->set('sisp.invoice.company_code', 'TAX-1');
    config()->set('sisp.invoice.company_email', 'billing@acme.test');
    config()->set('sisp.invoice.company_country', 'AO');
    config()->set('sisp.invoice.company_phone', '+244...');
    config()->set('sisp.invoice.company_website', 'https://acme.test');
    app()->instance(PdfGeneratorContract::class, new UpdateInvoiceStatusTestPdfGenerator());
    Storage::fake('public');

    $transaction = Transaction::factory()->create(['status' => 'completed']);
    $transaction->items()->create([
        'product_id' => '1',
        'product_name' => 'Test Product',
        'quantity' => 1,
        'unit_price_cents' => 10000,
        'total_price_cents' => 10000,
    ]);
    $invoice = resolve(GenerateInvoiceAction::class)->handle($transaction);

    expect($invoice->pdf_path)->toBeNull();

    $action = resolve(UpdateInvoiceStatusAction::class);
    $action->handle($invoice->transaction, TransactionStatus::completed);

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::paid)
        ->and($invoice->pdf_path)->not->toBeNull();
});

it('does not generate pdf if it already exists when transaction completes', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'completed']);
    $invoice = resolve(GenerateInvoiceAction::class)->handle($transaction);

    // Simulate PDF already exists
    $invoice->update(['pdf_path' => 'invoices/existing.pdf']);

    $action = resolve(UpdateInvoiceStatusAction::class);
    $action->handle($invoice->transaction, TransactionStatus::completed);

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::paid)
        ->and($invoice->pdf_path)->toBe('invoices/existing.pdf');
});

it('does not generate pdf when transaction is not completed', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending']);
    $invoice = resolve(GenerateInvoiceAction::class)->handle($transaction);

    $action = resolve(UpdateInvoiceStatusAction::class);
    $action->handle($invoice->transaction, TransactionStatus::pending);

    $invoice->refresh();
    expect($invoice->pdf_path)->toBeNull();
});

it('handles transaction without invoice gracefully', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending']);
    // Don't create an invoice

    $action = resolve(UpdateInvoiceStatusAction::class);
    // Should not throw an exception
    $action->handle($transaction, TransactionStatus::pending);

    expect(true)->toBeTrue();
});

it('does not fail the payment flow when pdf generation throws', function (): void {
    app()->instance(PdfGeneratorContract::class, new class implements PdfGeneratorContract
    {
        public function generate(DtoInvoiceData $invoice, string $template = 'modern'): string
        {
            throw new RuntimeException('headless browser unavailable');
        }

        public function save(DtoInvoiceData $invoice, string $template = 'modern', ?string $path = null): string
        {
            throw new RuntimeException('headless browser unavailable');
        }
    });

    Log::shouldReceive('error')->once()->withArgs(
        fn (string $message): bool => $message === 'SISP invoice PDF generation failed.'
    );

    $transaction = Transaction::factory()->create(['status' => 'completed']);
    $invoice = resolve(GenerateInvoiceAction::class)->handle($transaction);

    resolve(UpdateInvoiceStatusAction::class)->handle($transaction->refresh(), TransactionStatus::completed);

    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::paid)
        ->and($invoice->pdf_path)->toBeNull();
});
