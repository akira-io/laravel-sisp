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
    public ?DtoInvoiceData $lastInvoice = null;

    public function generate(DtoInvoiceData $invoice, string $template = 'modern'): string
    {
        $this->lastInvoice = $invoice;

        return '%PDF-WITH-ITEMS%';
    }

    public function save(DtoInvoiceData $invoice, string $template = 'modern', ?string $path = null): string
    {
        return '%PDF-SAVED%';
    }
}

it('includes transaction items when generating invoice PDF', function (): void {
    $pdfGenerator = new TestPdfGeneratorItems();
    app()->instance(PdfGeneratorContract::class, $pdfGenerator);
    Storage::fake('public');

    $transaction = Transaction::factory()->create([
        'customer_name' => 'John',
        'customer_email' => 'john@example.test',
    ]);

    TransactionItem::factory()->forTransaction($transaction)->create([
        'product_name' => 'Adult ticket',
        'quantity' => 2,
        'unit_price_cents' => 1250,
        'total_price_cents' => 2500,
    ]);
    TransactionItem::factory()->forTransaction($transaction)->create([
        'product_name' => 'Child ticket',
        'quantity' => 1,
        'unit_price_cents' => 750,
        'total_price_cents' => 750,
    ]);

    $invoice = resolve(GenerateInvoiceAction::class)->handle($transaction);
    $relativePath = resolve(GenerateInvoicePdfAction::class)->handle($invoice);

    Storage::disk('public')->assertExists($relativePath);
    expect($pdfGenerator->lastInvoice)->not->toBeNull()
        ->and($pdfGenerator->lastInvoice?->items)->toHaveCount(2)
        ->and($pdfGenerator->lastInvoice?->items[0]->description)->toBe('Adult ticket')
        ->and($pdfGenerator->lastInvoice?->items[0]->quantity)->toBe(2)
        ->and($pdfGenerator->lastInvoice?->items[0]->unitPrice)->toBe(12.5)
        ->and($pdfGenerator->lastInvoice?->items[0]->getTotal())->toBe(25.0)
        ->and($pdfGenerator->lastInvoice?->items[1]->description)->toBe('Child ticket')
        ->and($pdfGenerator->lastInvoice?->items[1]->quantity)->toBe(1)
        ->and($pdfGenerator->lastInvoice?->items[1]->unitPrice)->toBe(7.5)
        ->and($pdfGenerator->lastInvoice?->items[1]->getTotal())->toBe(7.5);
});
