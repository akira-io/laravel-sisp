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

it('aggregates matching transaction items when generating invoice PDF', function (): void {
    $pdfGenerator = new TestPdfGeneratorItems();
    app()->instance(PdfGeneratorContract::class, $pdfGenerator);
    Storage::fake('public');

    $transaction = Transaction::factory()->create([
        'customer_name' => 'John',
        'customer_email' => 'john@example.test',
    ]);

    TransactionItem::factory()->forTransaction($transaction)->create([
        'product_name' => 'Adult ticket',
        'quantity' => 1,
        'unit_price_cents' => 1250,
        'total_price_cents' => 1250,
    ]);
    TransactionItem::factory()->forTransaction($transaction)->create([
        'product_name' => 'Adult ticket',
        'quantity' => 1,
        'unit_price_cents' => 1250,
        'total_price_cents' => 1250,
    ]);
    TransactionItem::factory()->forTransaction($transaction)->create([
        'product_name' => 'Adult ticket',
        'quantity' => 1,
        'unit_price_cents' => 1000,
        'total_price_cents' => 1000,
    ]);
    TransactionItem::factory()->forTransaction($transaction)->create([
        'product_name' => 'Child ticket',
        'quantity' => 3,
        'unit_price_cents' => 750,
        'total_price_cents' => 2250,
    ]);

    expect($transaction->items()->count())->toBe(4);

    $invoice = resolve(GenerateInvoiceAction::class)->handle($transaction);
    $relativePath = resolve(GenerateInvoicePdfAction::class)->handle($invoice);

    $invoiceItems = collect($pdfGenerator->lastInvoice?->items)
        ->keyBy(fn ($item): string => $item->description.'|'.(int) round($item->unitPrice * 100));

    Storage::disk('public')->assertExists($relativePath);
    expect($pdfGenerator->lastInvoice)->not->toBeNull()
        ->and($invoiceItems)->toHaveCount(3)
        ->and($invoiceItems->get('Adult ticket|1250')->quantity)->toBe(2)
        ->and($invoiceItems->get('Adult ticket|1250')->unitPrice)->toBe(12.5)
        ->and($invoiceItems->get('Adult ticket|1250')->getTotal())->toBe(25.0)
        ->and($invoiceItems->get('Adult ticket|1000')->quantity)->toBe(1)
        ->and($invoiceItems->get('Adult ticket|1000')->unitPrice)->toBe(10.0)
        ->and($invoiceItems->get('Adult ticket|1000')->getTotal())->toBe(10.0)
        ->and($invoiceItems->get('Child ticket|750')->quantity)->toBe(3)
        ->and($invoiceItems->get('Child ticket|750')->unitPrice)->toBe(7.5)
        ->and($invoiceItems->get('Child ticket|750')->getTotal())->toBe(22.5)
        ->and($transaction->items()->count())->toBe(4);
});
