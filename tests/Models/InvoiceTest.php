<?php

declare(strict_types=1);

use Akira\Sisp\Models\Invoice;
use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Storage;

it('returns null pdf_url when pdf_path missing and resolves items relation', function (): void {
    $t = Transaction::factory()->create();
    $invoice = Invoice::query()->create([
        'transaction_id' => $t->id,
        'invoice_number' => 'INV-1',
        'invoice_date' => now(),
        'status' => 'pending',
    ]);

    expect($invoice->pdf_url)->toBeNull();
    // Ensure items() relation executes
    $itemsRelation = $invoice->items();
    expect(method_exists($itemsRelation, 'getResults'))->toBeTrue();
});

it('computes pdf_url from public disk and respects table config', function (): void {
    Storage::fake('public');
    config()->set('sisp.tables.invoices', 'sisp_invoices');

    $t = Transaction::factory()->create();
    $invoice = Invoice::query()->create([
        'transaction_id' => $t->id,
        'invoice_number' => 'INV-2',
        'invoice_date' => now(),
        'status' => 'pending',
        'pdf_path' => 'invoices/test.pdf',
    ]);

    // Write file so url() works on fake
    Storage::disk('public')->put('invoices/test.pdf', 'PDF');

    expect($invoice->getTable())->toBe('sisp_invoices')
        ->and($invoice->pdf_url)->not->toBeNull();
});

it('items relation proxies transaction items', function (): void {
    $t = Transaction::factory()->create();
    // Criar um item
    \DB::table(config('sisp.tables.transaction_items', 'sisp_transaction_items'))->insert([
        'transaction_id' => $t->id,
        'product_name' => 'Item 1',
        'quantity' => 1,
        'unit_price_cents' => 100,
        'total_price_cents' => 100,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $invoice = Invoice::query()->create([
        'transaction_id' => $t->id,
        'invoice_number' => 'INV-3',
        'invoice_date' => now(),
        'status' => 'pending',
    ]);

    expect($invoice->items()->count())->toBe(1);
});
